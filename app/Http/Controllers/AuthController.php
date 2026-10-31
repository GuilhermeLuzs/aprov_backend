<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as Status;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'app';

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $customer = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'role' => UserRole::Customer,
            ]);

            return $user->customer()->create([
                'username' => $request->validated('username'),
                'cpf' => $request->validated('cpf'),
                'birth_date' => $request->validated('birthDate'),
            ]);
        });

        return $this->session($customer, 'Conta criada.', Status::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with('customer')->where('email', $request->validated('email'))->first();

        if (! $user || $user->role !== UserRole::Customer || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->sendError('E-mail ou senha incorretos.', [], Status::HTTP_UNAUTHORIZED);
        }

        return $this->session($user->customer, 'Login feito.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse(null, 'Sessão encerrada.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->sendResponse(new CustomerResource($request->user()->customer));
    }

    private function session(Customer $customer, string $message, int $status = Status::HTTP_OK): JsonResponse
    {
        $token = $customer->user->createToken(self::TOKEN_NAME)->plainTextToken;

        return $this->sendResponse([
            'token' => $token,
            'customer' => new CustomerResource($customer),
        ], $message, $status);
    }
}
