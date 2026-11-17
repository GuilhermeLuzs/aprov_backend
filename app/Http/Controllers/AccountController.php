<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Resources\CustomerResource;
use App\Models\AccountAccessEvent;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    private const AVATAR_MAX_KB = 5 * 1024;

    public function updateProfile(Request $request): JsonResponse
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
        $customer = $this->viewerCustomer($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._]+$/', Rule::unique('customers', 'username')->ignore($customer->id)],
            'bio' => ['nullable', 'string', 'max:160'],
        ], [
            'name.*' => 'Informe o nome, com até 60 caracteres.',
            'username.unique' => 'Esse nome de usuário já está em uso.',
            'username.*' => 'Use de 3 a 30 letras minúsculas, números, ponto ou sublinhado.',
            'bio.max' => 'Use até 160 caracteres.',
        ]);

        DB::transaction(function () use ($customer, $data) {
            $customer->user->update(['name' => trim($data['name'])]);
            $customer->update(['username' => $data['username'], 'bio' => trim((string) ($data['bio'] ?? ''))]);
        });

        return $this->sendResponse(new CustomerResource($customer->fresh('user')), 'Perfil salvo.');
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate(
            ['avatar' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.self::AVATAR_MAX_KB, 'dimensions:max_width=6000,max_height=6000']],
            ['avatar.*' => 'Use uma foto JPG, PNG ou WebP de até 5 MB.'],
        );
        $user = $request->user();
        $old = $user->avatar_path;
        $user->update(['avatar_path' => $request->file('avatar')->store("avatars/{$user->id}", 'public')]);
        $this->deleteAvatar($old);

        return $this->sendResponse(new CustomerResource($user->customer->fresh('user')), 'Foto salva.');
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->deleteAvatar($user->avatar_path);
        $user->update(['avatar_path' => null]);

        return $this->sendResponse(new CustomerResource($user->customer->fresh('user')), 'Foto removida.');
    }

    public function deactivate(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $customer = $user->customer;
            Review::query()->where('customer_id', $customer?->id)->whereNull('author_removed_at')->update(['author_removed_at' => now()]);
            ReviewComment::query()->where('user_id', $user->id)->delete();
            self::closeAccount($user);
        });

        return $this->sendResponse(null, 'Conta desativada.');
    }

    public static function closeAccount(User $user): void
    {
        $user->update(['status' => UserStatus::Inactive]);
        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        AccountAccessEvent::create([
            'user_id' => $user->id,
            'applied_by' => $user->id,
            'action' => 'suspend',
            'reason' => 'selfDeactivation',
            'note' => 'A própria conta pediu para desativar.',
        ]);
    }

    private function deleteAvatar(?string $path): void
    {
        if ($path && str_starts_with($path, 'avatars/') && substr_count($path, '/') > 1) {
            Storage::disk('public')->delete($path);
        }
    }
}
