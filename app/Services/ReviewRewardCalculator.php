<?php

namespace App\Services;

use App\Enums\CoinRuleTopic;
use App\Enums\ReviewMediaType;
use App\Models\CustomerCoinBalance;
use App\Models\Review;
use App\Models\ReviewReward;
use App\Support\CoinRuleTopicEvaluator;
use App\Support\CoinRuleTopicFacts;
use Illuminate\Support\Facades\DB;

class ReviewRewardCalculator
{
    private const XP_PER_LEVEL = 200;

    public function __construct(private readonly CommentCoherenceChecker $coherenceChecker)
    {
    }

    public function credit(Review $review): ReviewReward
    {
        $review->loadMissing(['item.company.coinRuleTopics', 'tags', 'media']);

        $company = $review->item->company;
        $topics = $company?->coinRuleTopics ?? collect();

        $needsCoherenceCheck = $topics->contains('topic', CoinRuleTopic::CoherentComment);

        $facts = new CoinRuleTopicFacts(
            completed: true,
            tagCount: $review->tags->count(),
            hasVideo: $review->media->contains('type', ReviewMediaType::Video),
            photoCount: $review->media->where('type', ReviewMediaType::Photo)->where('valid', true)->count(),
            commentLength: mb_strlen($review->comment),
            coherentComment: $needsCoherenceCheck && $this->coherenceChecker->isCoherent($review->comment, $review->tags->pluck('name')->all()),
        );

        $awarded = $topics->filter(fn ($topic) => CoinRuleTopicEvaluator::satisfies($topic->topic, $topic->threshold, $facts));

        $coins = (int) $awarded->sum('coins');
        $points = (int) $awarded->sum('points');
        $xp = (int) $awarded->sum('xp');

        return DB::transaction(function () use ($review, $company, $awarded, $coins, $points, $xp) {
            if ($company && $coins > 0) {
                CustomerCoinBalance::query()->updateOrCreate(
                    ['customer_id' => $review->customer_id, 'company_id' => $company->id],
                    []
                )->increment('balance', $coins);
            }

            $customer = $review->customer;
            $customer->increment('points_balance', $points);
            $customer->increment('xp', $xp);
            $customer->level = intdiv($customer->xp, self::XP_PER_LEVEL) + 1;
            $customer->save();

            return ReviewReward::create([
                'review_id' => $review->id,
                'coins' => $coins,
                'points' => $points,
                'xp' => $xp,
                'topics_awarded' => $awarded->pluck('topic')->map(fn (CoinRuleTopic $topic) => $topic->value)->values()->all(),
            ]);
        });
    }
}
