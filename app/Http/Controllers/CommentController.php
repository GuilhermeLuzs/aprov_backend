<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as Status;

class CommentController extends Controller
{
    private const COMMENTS_PER_PAGE = 5;

    public function index(Request $request, Review $review): JsonResponse
    {
        $viewer = $this->viewer($request);

        $comments = ReviewComment::query()
            ->withTrashed()
            ->forViewer($viewer)
            ->where('review_id', $review->id)
            ->whereNull('parent_id')
            ->where(fn (Builder $q) => $q->whereNull('review_comments.deleted_at')->orHas('replies'))
            ->with([
                'user.customer',
                'replies' => fn ($replies) => $replies->forViewer($viewer)->with('user.customer'),
            ])
            ->orderByDesc('agreements_count')
            ->orderByDesc('review_comments.created_at')
            ->orderByDesc('review_comments.id')
            ->paginate(self::COMMENTS_PER_PAGE);

        return $this->sendPage($comments, 'comments', CommentResource::collection($comments->getCollection()));
    }

    public function store(StoreCommentRequest $request, Review $review): JsonResponse
    {
        $parentId = $request->validated('parentId');

        if ($parentId && ! $review->comments()->whereKey($parentId)->whereNull('parent_id')->exists()) {
            return $this->sendError('Comentário não encontrado.', [], Status::HTTP_NOT_FOUND);
        }

        $comment = $review->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'body' => $request->validated('body'),
        ]);

        return $this->sendResponse($this->present($comment, $request->user()), 'Comentário publicado.', Status::HTTP_CREATED);
    }

    public function update(UpdateCommentRequest $request, ReviewComment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return $this->sendError('Comentário não encontrado.', [], Status::HTTP_NOT_FOUND);
        }

        $comment->update(['body' => $request->validated('body'), 'edited_at' => now()]);

        return $this->sendResponse($this->present($comment, $request->user()), 'Comentário editado.');
    }

    public function destroy(Request $request, ReviewComment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            return $this->sendError('Comentário não encontrado.', [], Status::HTTP_NOT_FOUND);
        }

        $comment->delete();

        return $this->sendResponse(null, 'Comentário apagado.');
    }

    private function present(ReviewComment $comment, User $viewer): CommentResource
    {
        $fresh = ReviewComment::query()
            ->forViewer($viewer)
            ->with('user.customer')
            ->findOrFail($comment->id);

        return new CommentResource($fresh);
    }
}
