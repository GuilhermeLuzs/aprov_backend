<?php

namespace App\Http\Controllers;

use App\Enums\ReviewMediaType;
use App\Http\Requests\HomeRequest;
use App\Http\Resources\ItemCardResource;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Support\FullTextSearch;
use App\Support\Interests;
use App\Support\PublicFileUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    private const COMPANIES_PER_PAGE = 5;

    private const ITEMS_PER_COMPANY = 6;

    public function index(HomeRequest $request): JsonResponse
    {
        $text = FullTextSearch::of($request->search());
        $searching = ! $text->isEmpty();
        $companyIds = $request->companyIds();
        $viewer = $this->viewerCustomer($request);
        $interests = Interests::of($viewer);
        $activeItems = fn (Builder $items) => $items->where('is_active', true);
        $matchingItems = fn (Builder $items) => $activeItems($items)
            ->when($searching, fn (Builder $q) => $text->constrain($q, 'title'));

        $companies = Company::query()
            ->select('companies.*')
            ->when($companyIds, fn (Builder $q) => $q->whereIn('id', $companyIds))
            ->when(
                $searching,
                fn (Builder $q) => $this->searchCompanies($q, $text, $matchingItems, $activeItems),
                fn (Builder $q) => $q->selectRaw('1 as name_matches')->whereHas('items', $activeItems),
            )
            ->tap(fn (Builder $q) => $interests->orderCompanies($q))
            ->orderBy('id')
            ->paginate(self::COMPANIES_PER_PAGE);

        $rows = $companies->getCollection()->map(fn (Company $company) => [
            'id' => $company->id,
            'name' => $company->name,
            'logoUrl' => PublicFileUrl::of($company->logo_url),
            'items' => ItemCardResource::collection(
                $this->topItems(
                    $company,
                    $company->name_matches ? $activeItems : $matchingItems,
                    $viewer,
                    $searching && ! $company->name_matches ? $text : null,
                    $interests,
                ),
            ),
        ]);

        return $this->sendResponse([
            'page' => $companies->currentPage(),
            'pageCount' => max(1, $companies->lastPage()),
            'companies' => $rows,
        ]);
    }

    private function searchCompanies(Builder $query, FullTextSearch $text, callable $matchingItems, callable $activeItems): Builder
    {
        [$nameCondition, $nameBindings] = $text->condition('name');
        [$titleScore, $titleScoreBindings] = $text->score('title');

        $bestItemScore = Item::query()
            ->selectRaw("MAX({$titleScore})", $titleScoreBindings)
            ->whereColumn('items.company_id', 'companies.id')
            ->where('is_active', true);

        $query
            ->selectRaw("{$nameCondition} as name_matches", $nameBindings)
            ->where(fn (Builder $q) => $q
                ->whereHas('items', $matchingItems)
                ->orWhere(fn (Builder $byName) => $byName->whereRaw($nameCondition, $nameBindings)->whereHas('items', $activeItems)));

        return $text->orderByRelevanceOf($text->orderByRelevance($query, 'name'), $bestItemScore);
    }

    private function topItems(Company $company, callable $filter, ?Customer $viewer, ?FullTextSearch $relevance, Interests $interests)
    {
        return $filter(Item::query()->where('company_id', $company->id))
            ->forViewer($viewer)
            ->with([
                'media' => fn ($media) => $media->where('type', ReviewMediaType::Photo),
                'company.coinRuleTopics',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'average_rating')
            ->when($relevance, fn (Builder $q) => $relevance->orderByRelevance($q, 'title'))
            ->tap(fn (Builder $q) => $interests->orderItems($q))
            ->orderByDesc('reviews_avg_average_rating')
            ->orderByDesc('reviews_count')
            ->orderBy('id')
            ->limit(self::ITEMS_PER_COMPANY)
            ->get();
    }
}
