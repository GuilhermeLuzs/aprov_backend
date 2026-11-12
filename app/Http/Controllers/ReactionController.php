<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Http\Requests\ReactionRequest;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as Status;

class ReactionController extends Controller
{
    public function reactToReview(ReactionRequest $request, Review $review): JsonResponse
    {
        if ($review->status !== Review::STATUS_PUBLISHED) {
            return $this->sendError('Avaliação não encontrada.', [], Status::HTTP_NOT_FOUND);
        }

        if ($review->hasAuthor() && $review->customer?->user_id === $request->user()->id) {
            return $this->sendError('Você não pode votar na sua própria avaliação.', [], Status::HTTP_FORBIDDEN);
        }

        return $this->react($review->reactions(), $request);
    }

    public function removeReviewReaction(Request $request, Review $review): JsonResponse
    {
        return $this->remove($review->reactions(), $request);
    }

    public function reactToComment(ReactionRequest $request, ReviewComment $comment): JsonResponse
    {
        if ($comment->user_id === $request->user()->id) {
            return $this->sendError('Você não pode votar no seu próprio comentário.', [], Status::HTTP_FORBIDDEN);
        }

        return $this->react($comment->reactions(), $request);
    }

    public function removeCommentReaction(Request $request, ReviewComment $comment): JsonResponse
    {
        return $this->remove($comment->reactions(), $request);
    }

    private function react(HasMany $reactions, ReactionRequest $request): JsonResponse
    {
        $reactions->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['type' => $request->validated('type')],
        );

        return $this->tally($reactions, $request);
    }

    private function remove(HasMany $reactions, Request $request): JsonResponse
    {
        (clone $reactions)->where('user_id', $request->user()->id)->delete();

        return $this->tally($reactions, $request);
    }

    private function tally(HasMany $reactions, Request $request): JsonResponse
    {
        $mine = (clone $reactions)->where('user_id', $request->user()->id)->first();

        return $this->sendResponse([
            'agreements' => (clone $reactions)->where('type', ReactionType::Agree)->count(),
            'disagreements' => (clone $reactions)->where('type', ReactionType::Disagree)->count(),
            'myVote' => $mine?->type,
        ]);
    }
}
