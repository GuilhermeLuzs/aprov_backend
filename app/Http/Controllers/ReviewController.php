<?php

namespace App\Http\Controllers;

use App\Enums\ReviewMediaType;
use App\Enums\UserRole;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
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

        $media = $request->file('media', []);

        $review = DB::transaction(function () use ($request, $user, $ratings, $media) {
            $review = Review::create([
                'customer_id' => $user->client->id,
                'item_id' => $request->validated('itemId'),
                'delivery_rating' => $ratings[0],
                'quality_rating' => $ratings[1],
                'conformity_rating' => $ratings[2],
                'value_rating' => $ratings[3],
                'average_rating' => round(array_sum($ratings) / count($ratings), 1),
                'comment' => $request->validated('comment'),
                'would_buy_again' => $request->validated('wouldBuyAgain'),
                'media_consent' => $request->validated('mediaConsent') ?? false,
            ]);

            $review->tags()->sync($request->validated('tagIds'));

            foreach ($media as $position => $file) {
                $this->storeMedia($review, $file, $position);
            }

            return $review;
        });

        $review->load(['tags', 'media']);

        return $this->sendResponse(new ReviewResource($review), 'Avaliação publicada.', Status::HTTP_CREATED);
    }

    private function storeMedia(Review $review, UploadedFile $file, int $position): void
    {
        $isVideo = str_starts_with($file->getMimeType(), 'video/');
        $path = $file->store('reviews', 'public');

        $review->media()->create([
            'type' => $isVideo ? ReviewMediaType::Video : ReviewMediaType::Photo,
            'path' => $path,
            'position' => $position,
        ]);
    }
}
