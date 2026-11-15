<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterCompanyRequest;
use App\Http\Requests\UpdatePanelProfileRequest;
use App\Http\Resources\PanelAccountResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as Status;

class PanelAuthController extends Controller
{
    private const PANEL_ROLES = [UserRole::Company, UserRole::Master];

    public function login(LoginRequest $request): JsonResponse
    {
        if (! $request->hasSession()) {
            return $this->sendError('Entre pelo painel da APROV.', [], Status::HTTP_FORBIDDEN);
        }

        $user = User::query()->where('email', $request->validated('email'))->first();

        if (! $user || ! in_array($user->role, self::PANEL_ROLES, true) || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->sendError('E-mail ou senha incorretos.', [], Status::HTTP_UNAUTHORIZED);
        }

        if ($user->status !== UserStatus::Active) {
            return $this->sendError('Esta conta está inativa. Fale com o suporte da APROV.', [], Status::HTTP_FORBIDDEN);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return $this->sendResponse(new PanelAccountResource($user->load('company')), 'Login feito.');
    }

    public function register(RegisterCompanyRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('responsibleEmail'),
                'password' => $request->validated('password'),
                'role' => UserRole::Company,
            ]);

            $user->company()->create([
                'cnpj' => $request->validated('cnpj'),
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'zip_code' => $request->validated('zipCode'),
                'street' => $request->validated('street'),
                'number' => $request->validated('number'),
                'complement' => $request->validated('complement'),
                'district' => $request->validated('district'),
                'city' => $request->validated('city'),
                'state' => $request->validated('state'),
                'responsible_name' => $request->validated('responsibleName'),
                'responsible_cpf' => $request->validated('responsibleCpf'),
                'responsible_phone' => $request->validated('responsiblePhone'),
                'niche' => $request->validated('niche'),
                'modality' => $request->validated('modality'),
                'size' => $request->validated('size'),
            ]);
        });

        return $this->sendResponse(['email' => $request->validated('responsibleEmail')], 'Cadastro feito. Entre com o e-mail do responsável.', Status::HTTP_CREATED);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->sendResponse(new PanelAccountResource($request->user()->load('company')));
    }

    public function update(UpdatePanelProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->sendResponse(new PanelAccountResource($user->load('company')), 'Perfil salvo.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);
        $request->session()->regenerate();

        return $this->sendResponse(null, 'Senha alterada.');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->sendResponse(null, 'Sessão encerrada.');
    }
}
