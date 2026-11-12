<?php

namespace App\Http\Controllers;

use App\Enums\ItemState;
use App\Enums\ReviewMediaType;
use App\Http\Resources\ItemCardResource;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeItemController extends Controller
{
    private const ITEMS_PER_PAGE = 20;

    public function index(Request $request): JsonResponse
    {
        $request->validate(
            ['state' => ['required', Rule::enum(ItemState::class)]],
            ['state.required' => 'Escolha salvos ou comprados.', 'state.enum' => 'Escolha salvos ou comprados.'],
        );

        $customer = $request->user()->customer;

        $items = Item::query()
            ->whereHas('states', fn (Builder $q) => $q
                ->where('customer_id', $customer->id)
                ->where('type', $request->query('state')))
            ->forViewer($customer)
            ->with([
                'media' => fn ($media) => $media->where('type', ReviewMediaType::Photo),
                'company.coinRuleTopics',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'average_rating')
            ->orderByDesc(
                $customer->itemStates()
                    ->select('created_at')
                    ->whereColumn('customer_item_states.item_id', 'items.id')
                    ->where('type', $request->query('state'))
                    ->limit(1),
            )
            ->paginate(self::ITEMS_PER_PAGE);

        return $this->sendPage($items, 'items', ItemCardResource::collection($items->getCollection()));
    }

    public function update(Request $request, Item $item, ItemState $state): JsonResponse
    {
        $request->user()->customer->itemStates()->firstOrCreate(['item_id' => $item->id, 'type' => $state]);

        return $this->sendResponse(['state' => $state->value, 'active' => true]);
    }

    public function destroy(Request $request, Item $item, ItemState $state): JsonResponse
    {
        $request->user()->customer->itemStates()->where('item_id', $item->id)->where('type', $state)->delete();

        return $this->sendResponse(['state' => $state->value, 'active' => false]);
    }
}
