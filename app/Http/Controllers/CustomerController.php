<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Http\Resources\CustomerProfileResource;
use App\Http\Resources\ReviewResource;
use App\Models\Customer;
use App\Models\Review;
use App\Models\ReviewReaction;
use App\Models\User;
use App\Support\FullTextSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as Status;

class CustomerController extends Controller
{
    private const PEOPLE_PER_PAGE = 20;

    private const REVIEWS_PER_PAGE = 10;

    private const SEARCH_MAX_LENGTH = 100;

    public function index(Request $request): JsonResponse
    {
        $request->validate(
            ['search' => ['nullable', 'string', 'max:'.self::SEARCH_MAX_LENGTH]],
            ['search.max' => 'A busca pode ter até 100 caracteres.'],
        );

        $viewer = $this->viewerCustomer($request);
        $text = FullTextSearch::of((string) $request->query('search', ''));

        $people = $this->people($viewer)
            ->when($viewer, fn (Builder $q) => $q->whereKeyNot($viewer->id))
            ->when(
                ! $text->isEmpty(),
                fn (Builder $q) => $this->searchPeople($q, $text),
                fn (Builder $q) => $q->when($viewer, fn (Builder $q) => $q->whereDoesntHave(
                    'followers',
                    fn (Builder $followers) => $followers->where('follows.follower_id', $viewer->id),
                )),
            )
            ->orderByDesc('followers_count')
            ->orderBy('customers.id')
            ->paginate(self::PEOPLE_PER_PAGE);

        return $this->sendPage($people, 'people', CustomerProfileResource::collection($people->getCollection()));
    }

    public function show(Request $request, int $customer): JsonResponse
    {
        $found = $this->people($this->viewerCustomer($request))
            ->withCount([
                'reviews' => fn (Builder $q) => $q->published()->withAuthor(),
                'following',
            ])
            ->addSelect(['agreements_received' => ReviewReaction::query()
                ->selectRaw('count(*)')
                ->join('reviews', 'reviews.id', '=', 'review_reactions.review_id')
                ->whereColumn('reviews.customer_id', 'customers.id')
                ->whereNull('reviews.author_removed_at')
                ->where('review_reactions.type', ReactionType::Agree)])
            ->find($customer);

        if (! $found) {
            return $this->sendError('Perfil não encontrado.', [], Status::HTTP_NOT_FOUND);
        }

        return $this->sendResponse(new CustomerProfileResource($found));
    }

    public function reviews(Request $request, Customer $customer): JsonResponse
    {
        $reviews = $customer->reviews()
            ->published()
            ->withAuthor()
            ->forViewer($this->viewer($request))
            ->with(Review::CARD_RELATIONS)
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::REVIEWS_PER_PAGE);

        return $this->sendPage($reviews, 'reviews', ReviewResource::collection($reviews->getCollection()));
    }

    public function followers(Request $request, Customer $customer): JsonResponse
    {
        return $this->connections($request, $customer, 'following');
    }

    public function following(Request $request, Customer $customer): JsonResponse
    {
        return $this->connections($request, $customer, 'followers');
    }

    public function follow(Request $request, Customer $customer): JsonResponse
    {
        $viewer = $request->user()->customer;

        if ($viewer->is($customer)) {
            return $this->sendError('Você não pode seguir a si mesmo.', [], Status::HTTP_UNPROCESSABLE_ENTITY);
        }

        $viewer->following()->syncWithoutDetaching([$customer->id]);

        return $this->sendResponse(['isFollowing' => true], 'Seguindo.');
    }

    public function unfollow(Request $request, Customer $customer): JsonResponse
    {
        $request->user()->customer->following()->detach($customer->id);

        return $this->sendResponse(['isFollowing' => false], 'Deixou de seguir.');
    }

    private function searchPeople(Builder $query, FullTextSearch $text): Builder
    {
        [$nameScore, $nameBindings] = $text->score('name');

        $query->where(fn (Builder $match) => $match
            ->where(fn (Builder $q) => $text->constrain($q, 'username'))
            ->orWhereHas('user', fn (Builder $user) => $text->constrain($user, 'name')));

        return $text->orderByRelevanceOf(
            $text->orderByRelevance($query, 'username'),
            User::query()->selectRaw($nameScore, $nameBindings)->whereColumn('users.id', 'customers.user_id'),
        );
    }

    private function connections(Request $request, Customer $customer, string $inverse): JsonResponse
    {
        $column = $inverse === 'following' ? 'follows.followed_id' : 'follows.follower_id';

        $people = $this->people($this->viewerCustomer($request))
            ->whereHas($inverse, fn (Builder $q) => $q->where($column, $customer->id))
            ->orderBy('customers.id')
            ->paginate(self::PEOPLE_PER_PAGE);

        return $this->sendPage($people, 'people', CustomerProfileResource::collection($people->getCollection()));
    }

    private function people(?Customer $viewer): Builder
    {
        return Customer::query()
            ->select('customers.*')
            ->with('user')
            ->withCount('followers')
            ->when($viewer, fn (Builder $q) => $q->withExists([
                'followers as followed_by_viewer' => fn (Builder $followers) => $followers->where('follows.follower_id', $viewer->id),
            ]));
    }
}
