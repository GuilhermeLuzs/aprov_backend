<?php

namespace App\Http\Controllers;

use App\Http\Requests\RewardPreviewRequest;
use App\Http\Resources\CoinRuleTopicStatusResource;
use App\Models\Item;
use App\Support\CoinRuleTopicEvaluator;
use App\Support\CoinRuleTopicFacts;
use Illuminate\Http\JsonResponse;

class RewardPreviewController extends Controller
{
    public function show(RewardPreviewRequest $request, Item $item): JsonResponse
    {
        $item->loadMissing('company.coinRuleTopics');

        $facts = new CoinRuleTopicFacts(
            completed: $request->validated('draft.completed'),
            tagCount: $request->validated('draft.tagCount'),
            hasVideo: $request->validated('draft.hasVideo'),
            photoCount: $request->validated('draft.photoCount'),
            commentLength: $request->validated('draft.commentLength'),
            coherentComment: false,
        );

        $topics = $item->company?->coinRuleTopics ?? collect();
        $statuses = $topics->map(fn ($topic) => new CoinRuleTopicStatusResource($topic, $facts));

        $satisfied = $topics->filter(fn ($topic) => CoinRuleTopicEvaluator::satisfies($topic->topic, $topic->threshold, $facts));

        return $this->sendResponse([
            'topics' => $statuses->values(),
            'earned' => [
                'coins' => (int) $satisfied->sum('coins'),
                'aprovPoints' => (int) $satisfied->sum('points'),
                'xp' => (int) $satisfied->sum('xp'),
            ],
        ]);
    }
}
