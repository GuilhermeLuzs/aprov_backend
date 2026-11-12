<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Support\Interests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class FeedController extends Controller
{
    private const REVIEWS_PER_PAGE = 10;

    private const AUTHOR_GAP = 5;

    public function index(FeedRequest $request): JsonResponse
    {
        $viewer = $request->user();
        $customer = $viewer->customer;

        $query = Review::query()
            ->published()
            ->forViewer($viewer)
            ->with(Review::CARD_RELATIONS);

        if ($request->followingTab()) {
            $query->withAuthor()->whereIn('customer_id', $customer->following()->select('customers.id'));
        } else {
            Interests::of($customer)->orderReviews($query);
        }

        $page = $query
            ->orderByDesc('reviews.created_at')
            ->orderByDesc('reviews.id')
            ->paginate(self::REVIEWS_PER_PAGE);

        return $this->sendPage($page, 'reviews', ReviewResource::collection($this->spaceAuthors($page->getCollection())));
    }

    private function spaceAuthors(Collection $reviews): Collection
    {
        $pool = $reviews->values()->all();
        $result = [];

        while ($pool !== []) {
            $recentAuthors = array_map(fn (Review $r) => $r->customer_id, array_slice($result, -(self::AUTHOR_GAP - 1)));
            $pick = 0;
            foreach ($pool as $index => $review) {
                if (! in_array($review->customer_id, $recentAuthors, true)) {
                    $pick = $index;
                    break;
                }
            }
            $result[] = $pool[$pick];
            array_splice($pool, $pick, 1);
        }

        return collect($result);
    }
}
