<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as Status;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'app';

    public function register(RegisterClientRequest $request): JsonResponse
    {
        $client = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'role' => UserRole::Client,
            ]);

            return $user->client()->create([
                'username' => $request->validated('username'),
                'cpf' => $request->validated('cpf'),
                'birth_date' => $request->validated('birthDate'),
            ]);
        });

        return $this->session($client, 'Conta criada.', Status::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with('client')->where('email', $request->validated('email'))->first();

        if (! $user || $user->role !== UserRole::Client || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->sendError('E-mail ou senha incorretos.', [], Status::HTTP_UNAUTHORIZED);
        }

        return $this->session($user->client, 'Login feito.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse(null, 'Sessão encerrada.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->sendResponse(new ClientResource($request->user()->client));
    }

    private function session(Client $client, string $message, int $status = Status::HTTP_OK): JsonResponse
    {
        $token = $client->user->createToken(self::TOKEN_NAME)->plainTextToken;

        return $this->sendResponse([
            'token' => $token,
            'client' => new ClientResource($client),
        ], $message, $status);
    }
}
