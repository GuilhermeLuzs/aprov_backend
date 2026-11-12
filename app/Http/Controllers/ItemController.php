<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemDetailResource;
use App\Http\Resources\ItemResource;
use App\Http\Resources\TagResource;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as Status;

class ItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Item::query()
            ->where('is_active', true)
            ->with(['media', 'company.coinRuleTopics'])
            ->withCount('reviews')
            ->withAvg('reviews', 'average_rating')
            ->orderBy('id')
            ->get();

        return $this->sendResponse(ItemResource::collection($items));
    }

    public function show(Request $request, int $item): JsonResponse
    {
        $found = Item::query()
            ->forViewer($this->viewerCustomer($request))
            ->with(['media', 'company.coin', 'company.coinRuleTopics'])
            ->withCount('reviews')
            ->withAvg('reviews', 'average_rating')
            ->find($item);

        if (! $found) {
            return $this->sendError('Item não encontrado.', [], Status::HTTP_NOT_FOUND);
        }

        return $this->sendResponse(new ItemDetailResource($found));
    }

    public function tags(Item $item): JsonResponse
    {
        $categories = $item->categories ?? [];

        $tags = Tag::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (Tag $tag) => empty($tag->categories) || array_intersect($tag->categories, $categories) !== [])
            ->values();

        return $this->sendResponse(TagResource::collection($tags));
    }
}
