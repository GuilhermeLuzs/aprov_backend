<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\ItemKind;
use App\Http\Requests\ItemMetricsRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Review;
use App\Support\ReviewStats;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class MasterOverviewController extends Controller
{
    private const RECENT_DAYS = 30;

    private const VOLUME_MONTHS = 12;

    private const DAYS_IN_WEEK = 7;

    public function show(ItemMetricsRequest $request): JsonResponse
    {
        $since = now()->subDays(self::RECENT_DAYS);
        $items = Item::query()->get(['id', 'kind', 'categories']);
        $reviewedItems = Review::query()->published()->distinct()->count('item_id');

        $scoped = Review::query()->published()->whereBetween('reviews.created_at', [$request->start()->utc(), $request->end()->utc()]);

        return $this->sendResponse([
            'customers' => [
                'total' => Customer::query()->count(),
                'joined' => Customer::query()->where('created_at', '>=', $since)->count(),
                'active' => Customer::query()->where(fn (Builder $q) => $q
                    ->whereHas('user', fn (Builder $user) => $user->where('last_login_at', '>=', $since))
                    ->orWhereHas('reviews', fn (Builder $reviews) => $reviews->where('created_at', '>=', $since)))
                    ->count(),
            ],
            'companies' => [
                'total' => Company::query()->count(),
                'categories' => Company::query()->distinct()->count('niche'),
            ],
            'items' => [
                'total' => $items->count(),
                'products' => $items->where('kind', ItemKind::Product)->count(),
                'reviewed' => $reviewedItems,
            ],
            'categories' => collect(Category::cases())->map(fn (Category $category) => [
                'category' => $category->value,
                'product' => $items->filter(fn (Item $item) => $item->kind === ItemKind::Product && in_array($category->value, $item->categories, true))->count(),
                'service' => $items->filter(fn (Item $item) => $item->kind === ItemKind::Service && in_array($category->value, $item->categories, true))->count(),
            ])->values(),
            'volume' => $this->volume(),
            'days' => ReviewStats::days($scoped),
        ]);
    }

    private function volume(): array
    {
        $timezone = config('aprov.panel_timezone');
        $start = CarbonImmutable::now($timezone)->startOfMonth()->subMonths(self::VOLUME_MONTHS - 1);
        $dates = Review::query()->published()->where('created_at', '>=', $start->utc())->pluck('created_at')
            ->map(fn ($date) => CarbonImmutable::parse($date)->setTimezone($timezone));

        $byMonth = [];
        for ($month = $start; $month->lessThanOrEqualTo(CarbonImmutable::now($timezone)); $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $byMonth[] = ['month' => $key, 'count' => $dates->filter(fn ($date) => $date->format('Y-m') === $key)->count()];
        }

        $byWeekday = array_fill(0, self::DAYS_IN_WEEK, 0);
        $dates->each(function ($date) use (&$byWeekday) {
            $byWeekday[$date->dayOfWeek]++;
        });

        return ['byMonth' => $byMonth, 'byWeekday' => $byWeekday];
    }
}
