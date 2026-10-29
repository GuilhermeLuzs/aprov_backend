<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Item;
use App\Models\Review;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as Status;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Client || ! $user->client) {
            return $this->sendError('Só clientes podem publicar avaliações.', [], Status::HTTP_FORBIDDEN);
        }

        $ratings = [
            $request->validated('deliveryRating'),
            $request->validated('qualityRating'),
            $request->validated('conformityRating'),
            $request->validated('valueRating'),
        ];

        $item = Item::where('mock_id', $request->validated('itemId'))->firstOrFail();
        $tagIds = Tag::whereIn('mock_id', $request->validated('tagIds'))->pluck('id');

        $review = DB::transaction(function () use ($request, $user, $item, $tagIds, $ratings) {
            $review = Review::create([
                'customer_id' => $user->client->id,
                'item_id' => $item->id,
                'delivery_rating' => $ratings[0],
                'quality_rating' => $ratings[1],
                'conformity_rating' => $ratings[2],
                'value_rating' => $ratings[3],
                'average_rating' => round(array_sum($ratings) / count($ratings), 1),
                'comment' => $request->validated('comment'),
                'would_buy_again' => $request->validated('wouldBuyAgain'),
            ]);

            $review->tags()->sync($tagIds);

            return $review;
        });

        $review->load(['tags', 'item']);

        return $this->sendResponse(new ReviewResource($review), 'Avaliação publicada.', Status::HTTP_CREATED);
    }
}
