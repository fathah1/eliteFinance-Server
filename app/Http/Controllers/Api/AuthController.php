<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'shop_name' => ['nullable', 'string', 'max:255'],
            'activation_code' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'string', 'in:personal,business'],
            'outlet_count' => ['nullable', 'integer', 'min:1', 'required_if:account_type,business'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'account_owner_id' => null,
            'is_super_user' => true,
            'username' => $data['username'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => null,
            'shop_name' => $data['shop_name'] ?? $data['name'],
            'activation_code' => $data['activation_code'],
            'account_type' => $data['account_type'],
            'outlet_count' => $data['account_type'] === 'business' ? ($data['outlet_count'] ?? 1) : null,
            'password' => Hash::make($data['password']),
        ]);

        $defaultBusiness = Business::create([
            'user_id' => $user->id,
            'name' => $data['shop_name'] ?? $data['name'],
        ]);

        $token = $user->createToken('mobile')->plainTextToken;
        [$permissions, $businessIds] = $this->accessPayload($user);
        $offlineAuth = $this->ensureOfflineAuth($user);

        return response()->json([
            'user' => $user,
            'business' => $defaultBusiness,
            'business_ids' => $businessIds,
            'permissions' => $permissions,
            'offline_auth' => $offlineAuth,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $data['username'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;
        [$permissions, $businessIds] = $this->accessPayload($user);
        $offlineAuth = $this->ensureOfflineAuth($user);

        return response()->json([
            'user' => $user,
            'business_ids' => $businessIds,
            'permissions' => $permissions,
            'offline_auth' => $offlineAuth,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    private function accessPayload(User $user): array
    {
        $ownerId = $user->account_owner_id ?: $user->id;

        $helperRequest = request();
        $helperRequest->setUserResolver(fn () => $user);

        $permissions = $this->permissionMap($helperRequest);

        if ($user->is_super_user) {
            $businessIds = Business::where('user_id', $ownerId)->pluck('id')->map(fn ($v) => (int) $v)->all();
        } else {
            $businessIds = $this->businessIds($helperRequest);
        }

        return [$permissions, $businessIds];
    }

    private function ensureOfflineAuth(User $user): array
    {
        $dirty = false;
        if (empty($user->offline_auth_salt)) {
            $user->offline_auth_salt = Str::random(32);
            $dirty = true;
        }
        if (empty($user->offline_auth_version)) {
            $user->offline_auth_version = 1;
            $dirty = true;
        }
        if ($dirty) {
            $user->save();
        }

        return [
            'salt' => $user->offline_auth_salt,
            'version' => (int) ($user->offline_auth_version ?? 1),
        ];
    }
}
