<?php

namespace Tests\Unit;

use App\Enums\CoinRuleTopic;
use App\Support\CoinRuleTopicEvaluator;
use App\Support\CoinRuleTopicFacts;
use PHPUnit\Framework\TestCase;

class CoinRuleTopicEvaluatorTest extends TestCase
{
    private function facts(int $tagCount, bool $completed = false): CoinRuleTopicFacts
    {
        return new CoinRuleTopicFacts(
            completed: $completed,
            tagCount: $tagCount,
            hasVideo: false,
            photoCount: 0,
            commentLength: 0,
            coherentComment: false,
        );
    }

    public function test_tags_below_threshold_do_not_earn_the_topic(): void
    {
        $this->assertFalse(CoinRuleTopicEvaluator::satisfies(CoinRuleTopic::MinTags, 2, $this->facts(1)));
    }

    public function test_tags_at_threshold_earn_the_topic(): void
    {
        $this->assertTrue(CoinRuleTopicEvaluator::satisfies(CoinRuleTopic::MinTags, 2, $this->facts(2)));
    }

    public function test_incomplete_draft_does_not_earn_completed_review(): void
    {
        $this->assertFalse(CoinRuleTopicEvaluator::satisfies(CoinRuleTopic::CompletedReview, null, $this->facts(2)));
        $this->assertTrue(CoinRuleTopicEvaluator::satisfies(CoinRuleTopic::CompletedReview, null, $this->facts(2, true)));
    }
}
