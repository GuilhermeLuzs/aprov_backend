<?php

namespace App\Http\Controllers;

use App\Enums\CoinRuleTopic;
use App\Enums\CoinTransactionType;
use App\Enums\ItemKind;
use App\Http\Requests\ItemMetricsRequest;
use App\Http\Requests\ItemStatusRequest;
use App\Http\Requests\SaveCoinSettingsRequest;
use App\Models\CoinTransaction;
use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as Status;

class PanelCoinController extends Controller
{
    private const DEFAULT_COIN = ['name' => 'Moedas', 'color' => '#B45309', 'icon' => 'coins', 'is_enabled' => false];

    public function show(Request $request): JsonResponse
    {
        return $this->sendResponse($this->settings($this->company($request)));
    }

    public function update(SaveCoinSettingsRequest $request): JsonResponse
    {
        $company = $this->company($request);
        $data = $request->validated();

        DB::transaction(function () use ($company, $data) {
            $company->coin()->updateOrCreate([], [
                'name' => $data['name'],
                'color' => mb_strtoupper($data['color']),
                'icon' => $data['icon'],
                'real_value_rate' => $data['valueInReais'],
            ]);

            foreach (ItemKind::cases() as $kind) {
                foreach (CoinRuleTopic::forKind($kind) as $topic) {
                    $rule = $data['rewards'][$kind->value][$topic->value];
                    $company->coinRuleTopics()->updateOrCreate(
                        ['kind' => $kind, 'topic' => $topic],
                        ['coins' => $rule['coins'], 'threshold' => $rule['threshold'] ?? null],
                    );
                }
            }
        });

        return $this->sendResponse($this->settings($company->fresh()), 'Configuração salva.');
    }

    public function status(ItemStatusRequest $request): JsonResponse
    {
        $company = $this->company($request);
        $company->coin()->updateOrCreate([], [...($company->coin ? [] : self::DEFAULT_COIN), 'is_enabled' => $request->boolean('active')]);

        return $this->sendResponse(
            $this->settings($company->fresh()),
            $request->boolean('active') ? 'Moedas ligadas.' : 'Moedas desligadas. Os clientes deixam de ganhar novas.',
        );
    }

    public function performance(ItemMetricsRequest $request): JsonResponse
    {
        $company = $this->company($request);
        $timezone = config('aprov.panel_timezone');
        $offset = CarbonImmutable::now($timezone)->format('P');
        $all = CoinTransaction::query()->where('company_id', $company->id);
        $scoped = (clone $all)->whereBetween('created_at', [$request->start()->utc(), $request->end()->utc()]);

        $totals = (clone $all)->selectRaw("SUM(CASE WHEN type = 'earned' THEN amount ELSE 0 END) as earned, SUM(CASE WHEN type = 'spent' THEN amount ELSE 0 END) as spent")->toBase()->first();

        $byDay = (clone $scoped)
            ->selectRaw("DATE(CONVERT_TZ(created_at, '+00:00', ?)) as day", [$offset])
            ->selectRaw("SUM(CASE WHEN type = 'earned' THEN amount ELSE 0 END) as earned")
            ->selectRaw("SUM(CASE WHEN type = 'spent' THEN amount ELSE 0 END) as redeemed")
            ->selectRaw('COUNT(DISTINCT review_id) as reviews, COUNT(DISTINCT coupon_redemption_id) as coupons')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = [];
        for ($day = $request->start(); $day->lessThanOrEqualTo($request->end()); $day = $day->addDay()) {
            $row = $byDay->get($day->toDateString());
            $days[] = [
                'day' => $day->toDateString(),
                'earned' => (int) ($row->earned ?? 0),
                'redeemed' => (int) ($row->redeemed ?? 0),
                'reviews' => (int) ($row->reviews ?? 0),
                'coupons' => (int) ($row->coupons ?? 0),
            ];
        }

        $topics = (clone $scoped)
            ->where('type', CoinTransactionType::Earned)
            ->selectRaw('topic, SUM(amount) as coins')
            ->groupBy('topic')
            ->orderByDesc('coins')
            ->toBase()
            ->get()
            ->map(fn ($row) => ['topic' => $row->topic, 'coins' => (int) $row->coins]);

        $period = collect($days);

        return $this->sendResponse([
            'everEarned' => (int) ($totals->earned ?? 0),
            'inHands' => (int) ($totals->earned ?? 0) - (int) ($totals->spent ?? 0),
            'period' => [
                'earned' => $period->sum('earned'),
                'reviews' => (clone $scoped)->where('type', CoinTransactionType::Earned)->distinct()->count('review_id'),
                'redeemed' => $period->sum('redeemed'),
                'coupons' => (clone $scoped)->where('type', CoinTransactionType::Spent)->distinct()->count('coupon_redemption_id'),
                'days' => $days,
                'topics' => $topics,
            ],
        ]);
    }

    private function company(Request $request): Company
    {
        $company = $request->user()->company;
        abort_unless($company, Status::HTTP_FORBIDDEN, 'Você não tem acesso a isso.');

        return $company;
    }

    private function settings(Company $company): array
    {
        $coin = $company->coin;
        $rules = $company->coinRuleTopics()->get();
        $rewards = [];

        foreach (ItemKind::cases() as $kind) {
            foreach (CoinRuleTopic::forKind($kind) as $topic) {
                $rule = $rules->first(fn ($found) => $found->kind === $kind && $found->topic === $topic);
                $rewards[$kind->value][$topic->value] = [
                    'coins' => $rule?->coins ?? 0,
                    'threshold' => $rule?->threshold,
                ];
            }
        }

        return [
            'enabled' => (bool) ($coin?->is_enabled ?? false),
            'name' => $coin?->name ?? '',
            'color' => $coin?->color ?? self::DEFAULT_COIN['color'],
            'icon' => $coin?->icon ?? self::DEFAULT_COIN['icon'],
            'valueInReais' => (float) ($coin?->real_value_rate ?? 0),
            'rewards' => $rewards,
        ];
    }
}
