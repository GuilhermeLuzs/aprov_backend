<?php

namespace App\Http\Controllers;

use App\Enums\ItemKind;
use App\Enums\TagSentiment;
use App\Http\Requests\ItemMetricsRequest;
use App\Models\Item;
use App\Models\Review;
use App\Support\ReviewStats;
use Illuminate\Http\JsonResponse;

class PanelOverviewController extends Controller
{
    private const RANKING_SIZE = 5;

    private const DAYS_IN_WEEK = 7;

    public function show(ItemMetricsRequest $request): JsonResponse
    {
        $companyId = $request->user()->company->id;
        $items = Item::query()->where('company_id', $companyId)->with('cover')->get();

        $scoped = Review::query()
            ->published()
            ->whereIn('item_id', $items->modelKeys())
            ->whereBetween('reviews.created_at', [$request->start()->utc(), $request->end()->utc()]);

        $counts = collect(ReviewStats::days($scoped))->keyBy('day');
        $byDay = [];
        $byWeekday = array_fill(0, self::DAYS_IN_WEEK, 0);

        for ($day = $request->start(); $day->lessThanOrEqualTo($request->end()); $day = $day->addDay()) {
            $count = (int) ($counts[$day->toDateString()]['count'] ?? 0);
            $byDay[] = ['day' => $day->toDateString(), 'count' => $count];
            $byWeekday[$day->dayOfWeek] += $count;
        }

        return $this->sendResponse([
            'totalReviews' => Review::query()->published()->whereIn('item_id', $items->modelKeys())->count(),
            'catalog' => [
                'total' => $items->count(),
                'products' => $items->where('kind', ItemKind::Product)->count(),
                'services' => $items->where('kind', ItemKind::Service)->count(),
                'categories' => $items->flatMap(fn (Item $item) => $item->categories)->countBy()->all(),
            ],
            'period' => [
                ...ReviewStats::totals($scoped),
                'days' => $counts->values()->all(),
                'byDay' => $byDay,
                'byWeekday' => $byWeekday,
                'positiveTags' => ReviewStats::topTags($scoped, TagSentiment::Positive),
                'negativeTags' => ReviewStats::topTags($scoped, TagSentiment::Negative),
                ...$this->ranking($scoped, $items->keyBy('id')),
            ],
        ]);
    }

    private function ranking($scoped, $items): array
    {
        $scores = (clone $scoped)
            ->selectRaw('item_id, AVG(average_rating) as average, COUNT(*) as count')
            ->groupBy('item_id')
            ->orderByDesc('average')
            ->orderByDesc('count')
            ->toBase()
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->item_id,
                'title' => $items[$row->item_id]->title,
                'kind' => $items[$row->item_id]->kind,
                'coverUrl' => $items[$row->item_id]->cover?->url(),
                'average' => round((float) $row->average, 2),
                'count' => (int) $row->count,
            ]);

        $bestCount = min(self::RANKING_SIZE, (int) ceil($scores->count() / 2));
        $worstCount = min(self::RANKING_SIZE, $scores->count() - $bestCount);

        return [
            'best' => $scores->take($bestCount)->values()->all(),
            'worst' => $scores->slice($scores->count() - $worstCount)->reverse()->values()->all(),
        ];
    }
}
