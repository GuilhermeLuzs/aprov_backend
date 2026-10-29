<?php

namespace App\Support;

class CoinRuleTopicFacts
{
    public function __construct(
        public readonly bool $completed,
        public readonly int $tagCount,
        public readonly bool $hasVideo,
        public readonly int $photoCount,
        public readonly int $commentLength,
        public readonly bool $coherentComment,
    ) {
    }
}
