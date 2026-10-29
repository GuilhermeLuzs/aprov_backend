<?php

namespace App\Support;

use App\Enums\CoinRuleTopic;

class CoinRuleTopicEvaluator
{
    public static function satisfies(CoinRuleTopic $topic, ?int $threshold, CoinRuleTopicFacts $facts): bool
    {
        return match ($topic) {
            CoinRuleTopic::CompletedReview => $facts->completed,
            CoinRuleTopic::MinTags => $facts->tagCount >= ($threshold ?? 1),
            CoinRuleTopic::SentVideo => $facts->hasVideo,
            CoinRuleTopic::MinValidPhotos => $facts->photoCount >= ($threshold ?? 1),
            CoinRuleTopic::MinCommentLength => $facts->commentLength >= ($threshold ?? 0),
            CoinRuleTopic::CoherentComment => $facts->coherentComment,
        };
    }
}
