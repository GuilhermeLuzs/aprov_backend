<?php

namespace App\Http\Controllers;

use App\Http\Requests\MauricioChatRequest;
use App\Models\Item;
use App\Services\MauricioAdvisor;
use App\Support\CoinRuleTopicFacts;
use Illuminate\Http\JsonResponse;

class MauricioChatController extends Controller
{
    public function __construct(private readonly MauricioAdvisor $advisor)
    {
    }

    public function reply(MauricioChatRequest $request, Item $item): JsonResponse
    {
        $facts = new CoinRuleTopicFacts(
            completed: $request->validated('draft.completed'),
            tagCount: $request->validated('draft.tagCount'),
            hasVideo: $request->validated('draft.hasVideo'),
            photoCount: $request->validated('draft.photoCount'),
            commentLength: $request->validated('draft.commentLength'),
            coherentComment: false,
        );

        $reply = $this->advisor->reply($item, $facts, $request->validated('messages'));

        return $this->sendResponse(['reply' => $reply]);
    }
}
