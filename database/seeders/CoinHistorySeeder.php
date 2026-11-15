<?php

namespace Database\Seeders;

use App\Enums\CoinRuleTopic;
use App\Enums\CoinTransactionType;
use App\Enums\ReviewMediaType;
use App\Models\CoinTransaction;
use App\Models\CustomerCoinBalance;
use App\Models\Review;
use App\Models\ReviewReward;
use App\Support\CoinRuleTopicEvaluator;
use App\Support\CoinRuleTopicFacts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoinHistorySeeder extends Seeder
{
    public function run(): void
    {
        Review::query()
            ->published()
            ->whereDoesntHave('reward')
            ->with(['tags', 'media', 'item.company.coinRuleTopics'])
            ->orderBy('id')
            ->chunkById(200, function ($reviews) {
                DB::transaction(fn () => $reviews->each(fn (Review $review) => $this->credit($review)));
            });
    }

    private function credit(Review $review): void
    {
        $company = $review->item?->company;
        $topics = $company?->coinRuleTopics ?? collect();

        $facts = new CoinRuleTopicFacts(
            completed: true,
            tagCount: $review->tags->count(),
            hasVideo: $review->media->contains('type', ReviewMediaType::Video),
            photoCount: $review->media->where('type', ReviewMediaType::Photo)->count(),
            commentLength: mb_strlen($review->comment),
            coherentComment: $review->tags->isNotEmpty(),
        );

        $awarded = $topics->filter(fn ($topic) => CoinRuleTopicEvaluator::satisfies($topic->topic, $topic->threshold, $facts));
        $coins = (int) $awarded->sum('coins');
        $points = (int) $awarded->sum('points');
        $moment = ['created_at' => $review->created_at, 'updated_at' => $review->created_at];

        $reward = new ReviewReward([
            'review_id' => $review->id,
            'coins' => $coins,
            'points' => $points,
            'xp' => (int) $awarded->sum('xp'),
            'topics_awarded' => $awarded->pluck('topic')->map(fn (CoinRuleTopic $topic) => $topic->value)->values()->all(),
        ]);
        $reward->forceFill($moment)->save();

        if (! $company || $coins === 0) {
            return;
        }

        foreach ($awarded->where('coins', '>', 0) as $topic) {
            (new CoinTransaction([
                'customer_id' => $review->customer_id,
                'company_id' => $company->id,
                'type' => CoinTransactionType::Earned,
                'amount' => $topic->coins,
                'review_id' => $review->id,
                'topic' => $topic->topic,
            ]))->forceFill($moment)->save();
        }

        CustomerCoinBalance::query()
            ->firstOrCreate(['customer_id' => $review->customer_id, 'company_id' => $company->id])
            ->increment('balance', $coins);
        $review->customer()->increment('points_balance', $points);
    }
}
