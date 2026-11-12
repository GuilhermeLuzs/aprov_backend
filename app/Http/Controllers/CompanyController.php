<?php

namespace App\Http\Controllers;

use App\Enums\ReviewMediaType;
use App\Http\Requests\CompanyItemsRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ItemCardResource;
use App\Models\Company;
use App\Models\Item;
use App\Support\FullTextSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as Status;

class CompanyController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    public function index(): JsonResponse
    {
        return $this->sendResponse(CompanyResource::collection($this->companies()->get()));
    }

    public function show(int $company): JsonResponse
    {
        $found = $this->companies()->find($company);

        if (! $found) {
            return $this->sendError('Empresa não encontrada.', [], Status::HTTP_NOT_FOUND);
        }

        return $this->sendResponse(new CompanyResource($found));
    }

    public function items(CompanyItemsRequest $request, Company $company): JsonResponse
    {
        $text = FullTextSearch::of($request->search());
        $categories = $request->listOf('categories');

        $items = Item::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->forViewer($this->viewerCustomer($request))
            ->when(! $text->isEmpty(), fn (Builder $q) => $text->orderByRelevance($text->constrain($q, 'title'), 'title'))
            ->when($request->listOf('kinds'), fn (Builder $q, array $kinds) => $q->whereIn('kind', $kinds))
            ->when($categories, fn (Builder $q) => $q->where(function (Builder $match) use ($categories) {
                foreach ($categories as $category) {
                    $match->orWhereJsonContains('categories', $category);
                }
            }))
            ->with([
                'media' => fn ($media) => $media->where('type', ReviewMediaType::Photo),
                'company.coinRuleTopics',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'average_rating')
            ->orderByDesc('reviews_avg_average_rating')
            ->orderByDesc('reviews_count')
            ->orderBy('id')
            ->paginate(self::ITEMS_PER_PAGE);

        return $this->sendPage($items, 'items', ItemCardResource::collection($items->getCollection()));
    }

    private function companies(): Builder
    {
        return Company::query()
            ->with(['coin', 'items' => fn ($query) => $query->where('is_active', true)])
            ->withCount(['reviews', 'followers'])
            ->withAvg('reviews', 'average_rating')
            ->orderBy('id');
    }
}
