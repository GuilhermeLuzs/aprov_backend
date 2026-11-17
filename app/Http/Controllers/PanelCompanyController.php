<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyRequest;
use App\Http\Resources\PanelAccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PanelCompanyController extends Controller
{
    private const COLUMNS = [
        'zipCode' => 'zip_code',
        'responsibleName' => 'responsible_name',
        'responsiblePhone' => 'responsible_phone',
    ];

    public function update(UpdateCompanyRequest $request): JsonResponse
    {
        $company = $request->user()->company;
        $company->update(collect($request->validated())->mapWithKeys(fn ($value, $key) => [self::COLUMNS[$key] ?? $key => $value])->all());

        return $this->sendResponse(new PanelAccountResource($request->user()->load('company')), 'Dados da empresa salvos.');
    }

    public function deactivate(Request $request): JsonResponse
    {
        $request->validate(
            ['password' => ['required', 'current_password:web']],
            ['password.*' => 'Senha incorreta.'],
        );
        $user = $request->user();

        DB::transaction(fn () => AccountController::closeAccount($user));
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->sendResponse(null, 'Conta da empresa desativada.');
    }
}
