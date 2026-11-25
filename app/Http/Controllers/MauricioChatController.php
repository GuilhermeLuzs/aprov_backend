<?php

namespace App\Http\Controllers;

use App\Http\Requests\MauricioChatRequest;
use App\Models\Item;
use App\Services\MauricioAdvisor;
use App\Support\CoinRuleTopicFacts;
use App\Support\MauricioDraft;
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

        $draft = new MauricioDraft(
            characteristics: $request->validated('draft.characteristics'),
            positiveTagIds: $request->validated('draft.positiveTagIds'),
            negativeTagIds: $request->validated('draft.negativeTagIds'),
            comment: $request->validated('draft.comment') ?? '',
        );

        $reply = $this->advisor->reply($item, $facts, $draft, $request->validated('messages'));

        return $this->sendResponse(['reply' => $reply]);
    }
}
