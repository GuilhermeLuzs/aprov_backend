<?php

namespace App\Http\Controllers;

use App\Enums\ItemKind;
use App\Enums\TagSentiment;
use App\Http\Requests\ItemMetricsRequest;
use App\Http\Requests\ItemStatusRequest;
use App\Http\Requests\PanelItemsRequest;
use App\Http\Requests\SaveItemRequest;
use App\Http\Resources\PanelItemResource;
use App\Models\Company;
use App\Models\Item;
use App\Models\ItemMedia;
use App\Models\Review;
use App\Support\FullTextSearch;
use App\Support\ReviewStats;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as Status;

class PanelItemController extends Controller
{
    private const DEFAULT_PAGE_SIZE = 5;

    private const TOP_TAGS = 5;

    public function index(PanelItemsRequest $request): JsonResponse
    {
        $company = $this->company($request);
        $filters = $request->validated();
        $text = FullTextSearch::of(trim((string) ($filters['search'] ?? '')));
        $sort = $filters['sort'] ?? 'title';
        $direction = $filters['direction'] ?? ($sort === 'title' ? 'asc' : 'desc');

        $query = $this->withScores(Item::query()->where('company_id', $company->id))
            ->with('cover')
            ->when(! $text->isEmpty(), fn (Builder $q) => $text->constrainColumns($q, ['title', 'description']))
            ->when($filters['categories'] ?? [], fn (Builder $q, array $categories) => $q->where(function (Builder $match) use ($categories) {
                foreach ($categories as $category) {
                    $match->orWhereJsonContains('categories', $category);
                }
            }))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('is_active', $status === 'active'))
            ->when($filters['kind'] ?? null, fn (Builder $q, string $kind) => $q->where('kind', $kind));

        match ($sort) {
            'average' => $query->orderByRaw('reviews_avg_average_rating IS NULL')->orderBy('reviews_avg_average_rating', $direction),
            'count' => $query->orderBy('reviews_count', $direction),
            default => $query->orderBy('title', $direction),
        };

        $page = $query->orderBy('title')->orderBy('id')->paginate((int) ($filters['perPage'] ?? self::DEFAULT_PAGE_SIZE));

        return $this->sendResponse([
            'page' => $page->currentPage(),
            'pageCount' => max(1, $page->lastPage()),
            'total' => $page->total(),
            'catalogSize' => Item::query()->where('company_id', $company->id)->count(),
            'categories' => $this->categoriesOf($company),
            'items' => PanelItemResource::collection($page->getCollection()),
        ]);
    }

    public function show(Item $panelItem): JsonResponse
    {
        return $this->sendResponse(new PanelItemResource($this->detailed($panelItem)));
    }

    public function store(SaveItemRequest $request): JsonResponse
    {
        $company = $this->company($request);

        $item = DB::transaction(function () use ($request, $company) {
            $item = Item::create($request->itemAttributes() + [
                'company_id' => $company->id,
                'kind' => $request->validated('kind'),
                'is_active' => true,
            ]);
            $this->syncMedia($item, $request->media());
            $this->syncCities($item, $request->cities());

            return $item;
        });

        $kind = $item->kind === ItemKind::Service ? 'Serviço' : 'Produto';

        return $this->sendResponse(new PanelItemResource($this->detailed($item)), "{$kind} cadastrado e ativo.", Status::HTTP_CREATED);
    }

    public function update(SaveItemRequest $request, Item $panelItem): JsonResponse
    {
        $removed = DB::transaction(function () use ($request, $panelItem) {
            $panelItem->update($request->itemAttributes());
            $removed = $this->syncMedia($panelItem, $request->media());
            $this->syncCities($panelItem, $request->cities());

            return $removed;
        });

        $this->deleteFiles($removed);

        return $this->sendResponse(new PanelItemResource($this->detailed($panelItem)), 'Alterações salvas.');
    }

    public function status(ItemStatusRequest $request, Item $panelItem): JsonResponse
    {
        $panelItem->update(['is_active' => $request->boolean('active')]);

        return $this->sendResponse(
            ['id' => $panelItem->id, 'active' => $panelItem->is_active],
            $panelItem->is_active ? 'Item ativado. Já aparece no app.' : 'Item inativado. Saiu do app.',
        );
    }

    public function metrics(ItemMetricsRequest $request, Item $panelItem): JsonResponse
    {
        $all = Review::query()->published()->where('item_id', $panelItem->id);
        $last = (clone $all)->max('created_at');
        $scoped = (clone $all)->whereBetween('created_at', [$request->start()->utc(), $request->end()->utc()]);

        return $this->sendResponse([
            'totalReviews' => (clone $all)->count(),
            'lastReviewDay' => $last ? CarbonImmutable::parse($last, 'UTC')->setTimezone(config('aprov.panel_timezone'))->toDateString() : null,
            'period' => [
                ...ReviewStats::totals($scoped),
                'days' => ReviewStats::days($scoped),
                'positiveTags' => ReviewStats::topTags($scoped, TagSentiment::Positive),
                'negativeTags' => ReviewStats::topTags($scoped, TagSentiment::Negative),
            ],
        ]);
    }

    private function company(Request $request): Company
    {
        $company = $request->user()->company;
        abort_unless($company, Status::HTTP_FORBIDDEN, 'Você não tem acesso a isso.');

        return $company;
    }

    private function withScores(Builder $query): Builder
    {
        return $query
            ->withCount(['reviews' => fn (Builder $q) => $q->published()])
            ->withAvg(['reviews' => fn (Builder $q) => $q->published()], 'average_rating');
    }

    private function detailed(Item $item): Item
    {
        return $this->withScores(Item::query()->whereKey($item->id))
            ->with(['cover', 'media', 'cities'])
            ->firstOrFail();
    }

    private function categoriesOf(Company $company): array
    {
        return Item::query()
            ->where('company_id', $company->id)
            ->pluck('categories')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    private function syncMedia(Item $item, array $media): Collection
    {
        $keep = array_map(fn (ItemMedia $file) => $file->id, $media);
        $removed = $item->media()->whereNotIn('id', $keep)->get();
        $item->media()->whereNotIn('id', $keep)->delete();

        foreach ($media as $position => $file) {
            $file->update(['item_id' => $item->id, 'position' => $position]);
        }

        return $removed;
    }

    private function syncCities(Item $item, array $cities): void
    {
        $item->cities()->delete();

        if ($cities !== []) {
            $item->cities()->createMany($cities);
        }
    }

    private function deleteFiles(Collection $media): void
    {
        $paths = $media->flatMap(fn (ItemMedia $file) => array_filter([$file->path, $file->cover_path]))->all();

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }
}
