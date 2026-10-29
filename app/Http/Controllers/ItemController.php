<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Item::query()
            ->where('is_active', true)
            ->withCount('reviews')
            ->withAvg('reviews', 'average_rating')
            ->orderBy('title')
            ->get();

        return $this->sendResponse(ItemResource::collection($items));
    }

    public function show(Item $item): JsonResponse
    {
        $item->loadCount('reviews')->loadAvg('reviews', 'average_rating');

        return $this->sendResponse(new ItemResource($item));
    }
}
