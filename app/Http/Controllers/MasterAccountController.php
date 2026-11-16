<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Enums\UserStatus;
use App\Http\Resources\PanelAccountResource;
use App\Models\AccountAccessEvent;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as Status;

class MasterAccountController extends Controller
{
    private const RESULT_LIMIT = 50;

    private const RECENT_DAYS = 30;

    private const RECENT_REVIEWS = 5;

    private const TOP_ITEMS = 5;

    private const JOINED_DAYS = ['7d' => 7, '30d' => 30, '90d' => 90];

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['customer', 'company'])],
            'query' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
            'uf' => ['nullable', 'string', 'size:2'],
            'joined' => ['nullable', Rule::in(['7d', '30d', '90d', 'older'])],
            'activity' => ['nullable', Rule::in(['recent', 'idle'])],
            'category' => ['nullable', 'string', 'max:30'],
            'size' => ['nullable', 'string', 'max:10'],
        ]);

        $customer = $data['kind'] === 'customer';
        $query = $customer ? Customer::query()->with('user') : Company::query()->with('user');
        $this->filterByQuery($query, trim((string) ($data['query'] ?? '')), $customer);

        $query->whereHas('user', function (Builder $user) use ($data) {
            if (! empty($data['status'])) {
                $user->where('status', $data['status'] === 'suspended' ? UserStatus::Inactive : UserStatus::Active);
            }
            if (($data['activity'] ?? null) === 'recent') {
                $user->where('last_login_at', '>=', now()->subDays(self::RECENT_DAYS));
            }
            if (($data['activity'] ?? null) === 'idle') {
                $user->where(fn ($q) => $q->whereNull('last_login_at')->orWhere('last_login_at', '<', now()->subDays(self::RECENT_DAYS)));
            }
        });

        if (! empty($data['joined'])) {
            $data['joined'] === 'older'
                ? $query->where('created_at', '<', now()->subDays(90))
                : $query->where('created_at', '>=', now()->subDays(self::JOINED_DAYS[$data['joined']]));
        }

        if (! $customer) {
            $query->when($data['uf'] ?? null, fn ($q, $uf) => $q->where('state', mb_strtoupper($uf)))
                ->when($data['category'] ?? null, fn ($q, $category) => $q->where('niche', $category))
                ->when($data['size'] ?? null, fn ($q, $size) => $q->where('size', $size));
        }

        $results = $query->orderBy('id')->limit(self::RESULT_LIMIT + 1)->get();

        return $this->sendResponse(
            $results->map(fn ($account) => $customer ? $this->customer($account) : $this->company($account))->values()
        );
    }

    public function showCustomer(int $customer): JsonResponse
    {
        $found = Customer::query()->with('user')->find($customer);
        abort_unless($found, Status::HTTP_NOT_FOUND, 'Conta não encontrada.');

        $reviews = Review::query()->where('customer_id', $found->id)->withTrashed();
        $reactions = DB::table('review_reactions')
            ->whereIn('review_id', (clone $reviews)->select('id'))
            ->selectRaw('SUM(type = ?) as agree, SUM(type = ?) as disagree', [ReactionType::Agree->value, ReactionType::Disagree->value])
            ->first();

        return $this->sendResponse([
            'customer' => $this->customer($found),
            'events' => $this->events($found->user_id),
            'reviews' => [
                'count' => (clone $reviews)->where('status', Review::STATUS_PUBLISHED)->whereNull('deleted_at')->count(),
                'average' => $this->rounded((clone $reviews)->where('status', Review::STATUS_PUBLISHED)->whereNull('deleted_at')->avg('average_rating')),
                'agreements' => (int) ($reactions->agree ?? 0),
                'disagreements' => (int) ($reactions->disagree ?? 0),
                'recent' => (clone $reviews)->with('item.company')->latest()->limit(self::RECENT_REVIEWS)->get()->map(fn (Review $review) => [
                    'id' => (string) $review->id,
                    'title' => $review->item?->title ?? 'Item removido',
                    'companyName' => $review->item?->company?->name ?? '',
                    'rating' => (float) $review->average_rating,
                    'createdAt' => $review->created_at->toIso8601String(),
                    'removed' => $review->status === Review::STATUS_REMOVED || $review->trashed(),
                ])->values(),
            ],
        ]);
    }

    public function showCompany(int $company): JsonResponse
    {
        $found = Company::query()->with('user')->find($company);
        abort_unless($found, Status::HTTP_NOT_FOUND, 'Conta não encontrada.');

        $items = Item::query()->where('company_id', $found->id);
        $reviews = Review::query()->published()->whereIn('item_id', (clone $items)->select('id'));

        return $this->sendResponse([
            'company' => $this->company($found),
            'events' => $this->events($found->user_id),
            'items' => ['total' => (clone $items)->count(), 'active' => (clone $items)->where('is_active', true)->count()],
            'reviews' => [
                'count' => (clone $reviews)->count(),
                'average' => $this->rounded((clone $reviews)->avg('average_rating')),
                'reviewers' => (clone $reviews)->distinct()->count('customer_id'),
            ],
            'topItems' => (clone $items)
                ->with('cover')
                ->withCount(['reviews' => fn (Builder $q) => $q->published()])
                ->withAvg(['reviews' => fn (Builder $q) => $q->published()], 'average_rating')
                ->orderByDesc('reviews_count')
                ->limit(self::TOP_ITEMS)
                ->get()
                ->map(fn (Item $item) => [
                    'id' => (string) $item->id,
                    'title' => $item->title,
                    'kind' => $item->kind,
                    'coverUrl' => $item->cover?->url(),
                    'active' => $item->is_active,
                    'count' => (int) $item->reviews_count,
                    'average' => $item->reviews_avg_average_rating === null ? null : round((float) $item->reviews_avg_average_rating, 2),
                ])->values(),
        ]);
    }

    public function changeAccess(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['customer', 'company'])],
            'id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['suspend', 'reactivate'])],
            'reason' => ['required', Rule::in(AccountAccessEvent::REASONS)],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['reason.*' => 'Escolha o motivo.', 'note.max' => 'Use até 500 caracteres.']);

        $account = $data['kind'] === 'customer' ? Customer::query()->find($data['id']) : Company::query()->find($data['id']);
        abort_unless($account, Status::HTTP_NOT_FOUND, 'Conta não encontrada.');
        $user = User::query()->findOrFail($account->user_id);
        $suspend = $data['action'] === 'suspend';

        DB::transaction(function () use ($user, $data, $request, $suspend) {
            $user->update(['status' => $suspend ? UserStatus::Inactive : UserStatus::Active]);
            if ($suspend) {
                $user->tokens()->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            AccountAccessEvent::create([
                'user_id' => $user->id,
                'applied_by' => $request->user()->id,
                'action' => $data['action'],
                'reason' => $data['reason'],
                'note' => $data['note'] ?? null,
            ]);
        });

        return $this->sendResponse(['events' => $this->events($user->id)], $suspend ? 'Acesso suspenso.' : 'Acesso reativado.');
    }

    private function filterByQuery(Builder $query, string $text, bool $customer): void
    {
        if ($text === '') {
            return;
        }

        if (preg_match('/^([uc])(\d{1,18})$/i', $text, $match)) {
            $query->whereKey((int) $match[2]);

            return;
        }

        if (str_contains($text, '@') && filter_var($text, FILTER_VALIDATE_EMAIL)) {
            $email = mb_strtolower($text);
            $customer
                ? $query->whereHas('user', fn ($user) => $user->where('email', $email))
                : $query->where(fn ($q) => $q->where('email', $email)->orWhereHas('user', fn ($user) => $user->where('email', $email)));

            return;
        }

        $digits = preg_replace('/\D/', '', $text);
        if (preg_match('/^[\d.\-\/\s]+$/', $text) && strlen($digits) === ($customer ? 11 : 14)) {
            $query->where($customer ? 'cpf' : 'cnpj', $digits);

            return;
        }

        if ($customer && str_starts_with($text, '@')) {
            $query->where('username', 'like', addcslashes(mb_substr($text, 1), '%_\\').'%');

            return;
        }

        $like = '%'.addcslashes($text, '%_\\').'%';
        $customer
            ? $query->where(fn ($q) => $q->whereHas('user', fn ($user) => $user->where('name', 'like', $like))->orWhere('username', 'like', $like))
            : $query->where('name', 'like', $like);
    }

    private function customer(Customer $customer): array
    {
        return [
            'id' => 'u'.$customer->id,
            'name' => $customer->user?->name ?? '',
            'username' => $customer->username,
            'email' => $customer->user?->email ?? '',
            'cpf' => $customer->cpf,
            'birthDate' => $customer->birth_date?->toDateString() ?? '',
            'city' => '',
            'uf' => '',
            'createdAt' => $customer->created_at->toIso8601String(),
            'lastActiveAt' => ($customer->user?->last_login_at ?? $customer->created_at)->toIso8601String(),
            'xp' => (int) $customer->xp,
            'points' => (int) $customer->points_balance,
            'status' => $customer->user?->status === UserStatus::Inactive ? 'suspended' : 'active',
        ];
    }

    private function company(Company $company): array
    {
        return [
            ...PanelAccountResource::company($company),
            'id' => 'c'.$company->id,
            'status' => $company->user?->status === UserStatus::Inactive ? 'suspended' : 'active',
            'createdAt' => $company->created_at->toIso8601String(),
        ];
    }

    private function rounded(mixed $average): ?float
    {
        return $average === null ? null : round((float) $average, 2);
    }

    private function events(int $userId): array
    {
        return AccountAccessEvent::query()->where('user_id', $userId)->latest('id')->get()->map(fn (AccountAccessEvent $event) => [
            'id' => (string) $event->id,
            'action' => $event->action,
            'reason' => $event->reason,
            'note' => $event->note ?? '',
            'createdAt' => $event->created_at->toIso8601String(),
            'byId' => (string) $event->applied_by,
        ])->values()->all();
    }
}
