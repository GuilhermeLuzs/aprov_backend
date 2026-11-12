<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Enums\ReviewMediaType;
use App\Enums\UserRole;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewMedia;
use App\Services\ReviewRewardCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as Status;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewRewardCalculator $rewardCalculator)
    {
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Customer || ! $user->customer) {
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
                'customer_id' => $user->customer->id,
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

        $review->load(['tags', 'media', 'item']);

        $this->rewardCalculator->credit($review);
        $review->load('reward');

        return $this->sendResponse(new ReviewResource($review), 'Avaliação publicada.', Status::HTTP_CREATED);
    }

    public function show(Request $request, int $review): JsonResponse
    {
        $viewer = $this->viewer($request);
        $found = Review::query()
            ->published()
            ->forViewer($viewer)
            ->with(Review::CARD_RELATIONS)
            ->find($review)
            ?->loadHistory();

        if (! $found) {
            return $this->sendError('Avaliação não encontrada.', [], Status::HTTP_NOT_FOUND);
        }

        if ($found->versions->isNotEmpty()) {
            $found->setAttribute('votes_by_version', $this->votesByVersion($found));
        }

        return $this->sendResponse(new ReviewResource($found));
    }

    private function votesByVersion(Review $review): array
    {
        $starts = $review->versions
            ->pluck('published_at')
            ->sort()
            ->values()
            ->push($review->edited_at);

        return $starts->map(function ($start, int $index) use ($review, $starts) {
            $end = $starts->get($index + 1);
            $inVersion = fn () => $review->reactions()
                ->when($index > 0, fn ($q) => $q->where('updated_at', '>=', $start))
                ->when($end, fn ($q) => $q->where('updated_at', '<', $end));

            return [
                'agreements' => $inVersion()->where('type', ReactionType::Agree)->count(),
                'disagreements' => $inVersion()->where('type', ReactionType::Disagree)->count(),
            ];
        })->all();
    }

    public function mine(Request $request): JsonResponse
    {
        $reviews = Review::query()
            ->where('customer_id', $request->user()->customer->id)
            ->withAuthor()
            ->with(['tags', 'media', 'reward', 'versions', 'item'])
            ->latest()
            ->get();

        return $this->sendResponse(ReviewResource::collection($reviews));
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        if (! $this->isEditableBy($request, $review)) {
            return $this->sendError('Avaliação não encontrada.', [], Status::HTTP_NOT_FOUND);
        }

        $ratings = [
            $request->validated('deliveryRating'),
            $request->validated('qualityRating'),
            $request->validated('conformityRating'),
            $request->validated('valueRating'),
        ];

        $keepMediaIds = array_map('intval', $request->validated('keepMediaIds') ?? []);
        $newMedia = $request->file('media', []);

        DB::transaction(function () use ($request, $review, $ratings, $keepMediaIds, $newMedia) {
            $review->loadMissing(['tags', 'media']);

            $review->versions()->create([
                'delivery_rating' => $review->delivery_rating,
                'quality_rating' => $review->quality_rating,
                'conformity_rating' => $review->conformity_rating,
                'value_rating' => $review->value_rating,
                'average_rating' => $review->average_rating,
                'comment' => $review->comment,
                'would_buy_again' => $review->would_buy_again,
                'tag_ids' => $review->tags->pluck('id')->values()->all(),
                'media_ids' => $review->media->pluck('id')->values()->all(),
                'published_at' => $review->edited_at ?? $review->created_at,
            ]);

            $review->update([
                'delivery_rating' => $ratings[0],
                'quality_rating' => $ratings[1],
                'conformity_rating' => $ratings[2],
                'value_rating' => $ratings[3],
                'average_rating' => round(array_sum($ratings) / count($ratings), 1),
                'comment' => $request->validated('comment'),
                'would_buy_again' => $request->validated('wouldBuyAgain'),
                'media_consent' => $request->validated('mediaConsent') ?? false,
                'edited_at' => now(),
            ]);

            $review->tags()->sync($request->validated('tagIds'));

            $isKept = fn (ReviewMedia $media) => in_array($media->id, $keepMediaIds, true);

            $review->media->reject($isKept)->each(fn (ReviewMedia $media) => $media->update(['removed_at' => now()]));

            $kept = $review->media->filter($isKept)->values();
            foreach ($kept as $position => $media) {
                $media->update(['position' => $position]);
            }

            foreach ($newMedia as $offset => $file) {
                $this->storeMedia($review, $file, $kept->count() + $offset);
            }
        });

        $review->unsetRelation('media');
        $review->load(['tags', 'media', 'reward', 'item'])->loadHistory();

        return $this->sendResponse(new ReviewResource($review), 'Avaliação editada.');
    }

    public function removeAuthor(Request $request, Review $review): JsonResponse
    {
        if (! $this->isEditableBy($request, $review)) {
            return $this->sendError('Avaliação não encontrada.', [], Status::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($review) {
            $review->allMedia->each(fn (ReviewMedia $media) => $this->deleteMedia($media));
            $review->update(['author_removed_at' => now()]);
        });

        return $this->sendResponse(null, 'Seu nome saiu da avaliação.');
    }

    private function isEditableBy(Request $request, Review $review): bool
    {
        $customer = $request->user()->customer;

        return $customer && $review->customer_id === $customer->id && $review->hasAuthor();
    }

    private function deleteMedia(ReviewMedia $media): void
    {
        Storage::disk('public')->delete(array_filter([$media->path, $media->cover_path]));
        $media->delete();
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
