<?php

namespace App\Support;

use App\Enums\ReactionType;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FollowSuggestions
{
    private const LIMIT = 10;

    private const RECENT_DAYS = 30;

    private const CATEGORY_LABEL = [
        'eletronicos' => 'Eletrônicos',
        'alimentacao' => 'Alimentação',
        'moda' => 'Moda',
        'servicos_domesticos' => 'Serviços domésticos',
        'beleza' => 'Beleza',
        'educacao' => 'Educação',
        'casa' => 'Casa',
        'saude' => 'Saúde',
    ];

    public function __construct(private readonly Customer $viewer) {}

    public function get(): Collection
    {
        $following = $this->viewer->following()->pluck('customers.id')->all();
        $companies = $this->viewerCompanies();
        $categories = $this->viewer->preferred_categories ?? [];

        $suggestions = Customer::query()
            ->select('customers.*')
            ->with('user')
            ->whereKeyNot($this->viewer->id)
            ->whereNotIn('customers.id', $following)
            ->whereHas('user', fn (Builder $user) => $user->where('status', UserStatus::Active))
            ->whereHas('reviews', fn (Builder $reviews) => $reviews->published()->withAuthor())
            ->withCount([
                'followers as friends_following' => fn (Builder $q) => $q->whereIn('follows.follower_id', $following ?: [0]),
                'followers',
                'reviews as shared_reviews' => fn (Builder $q) => $q->published()->withAuthor()
                    ->whereHas('item', fn (Builder $item) => $this->sameTaste($item, $companies, $categories)),
            ])
            ->addSelect(['recent_agreements' => DB::table('review_reactions')
                ->join('reviews', 'reviews.id', '=', 'review_reactions.review_id')
                ->whereColumn('reviews.customer_id', 'customers.id')
                ->where('review_reactions.type', ReactionType::Agree->value)
                ->where('review_reactions.created_at', '>=', now()->subDays(self::RECENT_DAYS))
                ->selectRaw('COUNT(*)')])
            ->orderByDesc('friends_following')
            ->orderByDesc('shared_reviews')
            ->orderByDesc('recent_agreements')
            ->orderByDesc('followers_count')
            ->orderBy('customers.id')
            ->limit(self::LIMIT)
            ->get();

        $suggestions->each(fn (Customer $customer) => $customer->setAttribute('suggestion_reason', $this->reason($customer, $following, $companies, $categories)));

        return $suggestions;
    }

    private function viewerCompanies(): array
    {
        $followed = $this->viewer->followedCompanies()->pluck('companies.id')->all();
        $reviewed = Item::query()
            ->whereIn('id', Review::query()->where('customer_id', $this->viewer->id)->select('item_id'))
            ->pluck('company_id')
            ->all();

        return array_values(array_unique([...$followed, ...$reviewed]));
    }

    private function sameTaste(Builder $item, array $companies, array $categories): void
    {
        $item->where(function (Builder $match) use ($companies, $categories) {
            $match->whereIn('company_id', $companies ?: [0]);
            foreach ($categories as $category) {
                $match->orWhereJsonContains('categories', $category);
            }
        });
    }

    private function reason(Customer $customer, array $following, array $companies, array $categories): string
    {
        if ($customer->friends_following > 0) {
            $friend = DB::table('follows')
                ->join('customers', 'customers.id', '=', 'follows.follower_id')
                ->join('users', 'users.id', '=', 'customers.user_id')
                ->where('follows.followed_id', $customer->id)
                ->whereIn('follows.follower_id', $following)
                ->orderBy('follows.follower_id')
                ->value('users.name');
            $others = $customer->friends_following - 1;

            return 'Seguido por '.strtok((string) $friend, ' ').($others > 0 ? " e mais {$others}" : '');
        }

        if ($customer->shared_reviews > 0) {
            $item = Item::query()
                ->with('company')
                ->whereIn('id', Review::query()->published()->withAuthor()->where('customer_id', $customer->id)->select('item_id'))
                ->where(fn (Builder $item) => $this->sameTaste($item, $companies, $categories))
                ->first();

            if ($item && in_array($item->company_id, $companies, true)) {
                return 'Também avalia '.$item->company?->name;
            }

            $category = collect($item?->categories ?? [])->first(fn ($value) => in_array($value, $categories, true));

            return 'Também avalia '.(self::CATEGORY_LABEL[$category] ?? 'o que você gosta');
        }

        return $customer->recent_agreements > 0 ? 'Em alta nas avaliações' : 'Avaliador na APROV';
    }
}
