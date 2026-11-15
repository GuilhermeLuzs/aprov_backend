<?php

namespace App\Support;

use App\Enums\TagSentiment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReviewStats
{
    private const AVERAGES = 'COUNT(*) as count, AVG(reviews.average_rating) as overall, AVG(reviews.delivery_rating) as delivery, AVG(reviews.quality_rating) as quality, AVG(reviews.conformity_rating) as conformity, AVG(reviews.value_rating) as value';

    private const TOP_TAGS = 5;

    public static function totals(Builder $reviews): array
    {
        $row = (clone $reviews)->selectRaw(self::AVERAGES)->toBase()->first();

        return ['count' => (int) $row->count, 'averages' => self::averages($row)];
    }

    public static function days(Builder $reviews): array
    {
        return (clone $reviews)
            ->selectRaw('DATE(CONVERT_TZ(reviews.created_at, \'+00:00\', ?)) as day', [self::offset()])
            ->selectRaw(self::AVERAGES)
            ->groupBy('day')
            ->orderBy('day')
            ->toBase()
            ->get()
            ->map(fn ($row) => ['day' => (string) $row->day, 'count' => (int) $row->count, ...self::averages($row)])
            ->all();
    }

    public static function topTags(Builder $reviews, TagSentiment $sentiment): array
    {
        return DB::table('review_tag')
            ->join('tags', 'tags.id', '=', 'review_tag.tag_id')
            ->whereIn('review_tag.review_id', (clone $reviews)->select('reviews.id'))
            ->where('tags.sentiment', $sentiment->value)
            ->groupBy('tags.id', 'tags.name')
            ->orderByDesc('count')
            ->orderBy('tags.name')
            ->limit(self::TOP_TAGS)
            ->get(['tags.id', 'tags.name', DB::raw('COUNT(*) as count')])
            ->map(fn ($tag) => ['id' => $tag->id, 'label' => $tag->name, 'count' => (int) $tag->count])
            ->all();
    }

    public static function offset(): string
    {
        return CarbonImmutable::now(config('aprov.panel_timezone'))->format('P');
    }

    private static function averages(object $row): array
    {
        $value = fn ($average) => $average === null ? null : round((float) $average, 2);

        return [
            'overall' => $value($row->overall),
            'delivery' => $value($row->delivery),
            'quality' => $value($row->quality),
            'conformity' => $value($row->conformity),
            'value' => $value($row->value),
        ];
    }
}
