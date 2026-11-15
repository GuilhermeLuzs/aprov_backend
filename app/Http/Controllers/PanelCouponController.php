<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as Status;

class PanelCouponController extends Controller
{
    private const MAX_CODES = 1000;

    public function index(Request $request): JsonResponse
    {
        $coupons = $request->user()->company->coupons()->with('codes')->latest('id')->get();

        return $this->sendResponse($coupons->map(fn (Coupon $coupon) => $this->coupon($coupon))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:300'],
            'coinCost' => ['required', 'integer', 'min:1', 'max:1000000'],
            'validUntil' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            ...$this->codeRules(),
        ], $this->messages());

        $coupon = DB::transaction(function () use ($request, $data) {
            $coupon = $request->user()->company->coupons()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'coin_cost' => $data['coinCost'],
                'valid_until' => $data['validUntil'],
            ]);
            $coupon->codes()->createMany(array_map(fn (string $code) => ['code' => $code], array_unique($data['codes'])));

            return $coupon;
        });

        return $this->sendResponse($this->coupon($coupon->load('codes')), 'Cupom criado.', Status::HTTP_CREATED);
    }

    public function addCodes(Request $request, Coupon $panelCoupon): JsonResponse
    {
        $data = $request->validate($this->codeRules($panelCoupon), $this->messages());
        $panelCoupon->codes()->createMany(array_map(fn (string $code) => ['code' => $code], array_unique($data['codes'])));

        return $this->sendResponse($this->coupon($panelCoupon->load('codes')), 'Códigos adicionados.');
    }

    public function removeCode(Coupon $panelCoupon, int $code): JsonResponse
    {
        $deleted = $panelCoupon->codes()->whereKey($code)->whereNull('coupon_redemption_id')->delete();
        abort_unless($deleted, Status::HTTP_NOT_FOUND, 'Código não encontrado ou já entregue.');

        return $this->sendResponse($this->coupon($panelCoupon->load('codes')), 'Código removido.');
    }

    public function destroy(Coupon $panelCoupon): JsonResponse
    {
        $panelCoupon->delete();

        return $this->sendResponse(null, 'Cupom removido. Quem já resgatou continua com o código.');
    }

    private function codeRules(?Coupon $coupon = null): array
    {
        return [
            'codes' => ['required', 'array', 'min:1', 'max:'.self::MAX_CODES],
            'codes.*' => [
                'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupon_codes', 'code')->where('coupon_id', $coupon?->id ?? 0),
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Dê um título ao cupom.',
            'title.max' => 'Use até 60 caracteres.',
            'description.max' => 'Use até 300 caracteres.',
            'coinCost.*' => 'Informe quantas moedas o cupom custa, 1 ou mais.',
            'validUntil.after_or_equal' => 'A validade não pode ser no passado.',
            'validUntil.*' => 'Informe a validade.',
            'codes.required' => 'Adicione pelo menos um código.',
            'codes.min' => 'Adicione pelo menos um código.',
            'codes.max' => 'Envie até '.self::MAX_CODES.' códigos de uma vez.',
            'codes.*.unique' => 'O código :input já está neste cupom.',
            'codes.*' => 'Código inválido: use de 3 a 60 letras, números, hífen ou sublinhado.',
        ];
    }

    private function coupon(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'title' => $coupon->title,
            'description' => $coupon->description,
            'coinCost' => $coupon->coin_cost,
            'validUntil' => $coupon->valid_until->toDateString(),
            'codes' => $coupon->codes->map(fn (CouponCode $code) => [
                'id' => $code->id,
                'code' => $code->code,
                'deliveredAt' => $code->delivered_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
