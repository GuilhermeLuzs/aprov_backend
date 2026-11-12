<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Item;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemReviewController extends Controller
{
    private const REVIEWS_PER_PAGE = 5;

    public function index(Request $request, Item $item): JsonResponse
    {
        $reviews = $item->reviews()
            ->published()
            ->forViewer($this->viewer($request))
            ->with(Review::CARD_RELATIONS)
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::REVIEWS_PER_PAGE);

        return $this->sendPage($reviews, 'reviews', ReviewResource::collection($reviews->getCollection()));
    }
}
