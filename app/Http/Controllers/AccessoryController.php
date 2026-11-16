<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Models\Customer;
use App\Models\PointTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as Status;

class AccessoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->sendResponse($this->state($this->viewerCustomer($request)));
    }

    public function buy(Request $request, string $slug): JsonResponse
    {
        $accessory = Accessory::query()->where('slug', $slug)->first();
        abort_unless($accessory, Status::HTTP_NOT_FOUND, 'Acessório não encontrado.');
        $customerId = $this->viewerCustomer($request)->id;

        $error = DB::transaction(function () use ($accessory, $customerId) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customerId);

            if (DB::table('accessory_customer')->where('customer_id', $customer->id)->where('accessory_id', $accessory->id)->exists()) {
                return null;
            }
            if ($customer->level < $accessory->required_level) {
                return "Este acessório libera no nível {$accessory->required_level}.";
            }
            if ($customer->points_balance < $accessory->price) {
                return 'Você não tem pontos APROV suficientes.';
            }

            $customer->decrement('points_balance', $accessory->price);
            DB::table('accessory_customer')->insert(['customer_id' => $customer->id, 'accessory_id' => $accessory->id, 'created_at' => now(), 'updated_at' => now()]);
            PointTransaction::create(['customer_id' => $customer->id, 'type' => PointTransaction::SPENT, 'amount' => $accessory->price, 'accessory_id' => $accessory->id]);

            return null;
        });

        if ($error) {
            return $this->sendError($error, [], Status::HTTP_CONFLICT);
        }

        return $this->sendResponse($this->state($this->viewerCustomer($request)->fresh()), 'Acessório comprado.');
    }

    public function equip(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in([Accessory::KIND_SKIN, Accessory::KIND_FRAME])],
            'accessoryId' => ['nullable', 'string', 'max:40'],
        ]);
        $customer = $this->viewerCustomer($request);
        $column = $data['kind'] === Accessory::KIND_SKIN ? 'equipped_skin_id' : 'equipped_frame_id';
        $accessoryId = null;

        if (! empty($data['accessoryId'])) {
            $accessoryId = DB::table('accessories')
                ->join('accessory_customer', 'accessory_customer.accessory_id', '=', 'accessories.id')
                ->where('accessory_customer.customer_id', $customer->id)
                ->where('accessories.slug', $data['accessoryId'])
                ->where('accessories.kind', $data['kind'])
                ->value('accessories.id');
            abort_unless($accessoryId, Status::HTTP_NOT_FOUND, 'Você ainda não tem este acessório.');
        }

        $customer->forceFill([$column => $accessoryId])->save();

        return $this->sendResponse($this->state($customer->fresh()), 'Pronto.');
    }

    private function state(Customer $customer): array
    {
        $owned = DB::table('accessory_customer')->where('customer_id', $customer->id)->pluck('accessory_id')->all();
        $accessories = Accessory::query()->orderBy('position')->get();
        $slugOf = fn (?int $id) => $id ? $accessories->firstWhere('id', $id)?->slug : null;

        return [
            'points' => (int) $customer->points_balance,
            'level' => (int) $customer->level,
            'equippedSkinId' => $slugOf($customer->equipped_skin_id),
            'equippedFrameId' => $slugOf($customer->equipped_frame_id),
            'accessories' => $accessories->map(fn (Accessory $accessory) => [
                'id' => $accessory->slug,
                'kind' => $accessory->kind,
                'asset' => $accessory->asset,
                'name' => $accessory->name,
                'description' => $accessory->description,
                'price' => $accessory->price,
                'level' => $accessory->required_level,
                'owned' => in_array($accessory->id, $owned, true),
            ])->values(),
        ];
    }
}
