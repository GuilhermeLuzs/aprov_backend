<?php

namespace App\Http\Resources;

use App\Models\CompanyCoinRuleTopic;
use App\Support\CoinRuleTopicEvaluator;
use App\Support\CoinRuleTopicFacts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CompanyCoinRuleTopic */
class CoinRuleTopicStatusResource extends JsonResource
{
    public function __construct(private readonly CompanyCoinRuleTopic $topic, private readonly CoinRuleTopicFacts $facts)
    {
        parent::__construct($topic);
    }

    public function toArray(Request $request): array
    {
        return [
            'topic' => $this->topic->topic->value,
            'threshold' => $this->topic->threshold,
            'satisfied' => CoinRuleTopicEvaluator::satisfies($this->topic->topic, $this->topic->threshold, $this->facts),
            'coins' => $this->topic->coins,
            'aprovPoints' => $this->topic->points,
            'xp' => $this->topic->xp,
        ];
    }
}
