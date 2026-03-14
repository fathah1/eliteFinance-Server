<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Models\UserBusinessAccess;
use App\Models\UserFeaturePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    private const FEATURES = ['parties', 'items', 'reports', 'sale', 'purchase', 'expense', 'bills'];

    public function staff(Request $request)
    {
        if (!$this->isSuperUser($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ownerId = $this->ownerUserId($request);
        $users = User::where('account_owner_id', $ownerId)
            ->where('is_super_user', false)
            ->orderBy('id', 'desc')
            ->get();

        $rows = $users->map(function ($u) use ($ownerId) {
            $businessIds = UserBusinessAccess::where('account_owner_id', $ownerId)
                ->where('user_id', $u->id)
                ->pluck('business_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $perms = UserFeaturePermission::where('account_owner_id', $ownerId)
                ->where('user_id', $u->id)
                ->get()
                ->keyBy('feature')
                ->map(fn ($p) => [
                    'view' => (bool) $p->can_view,
                    'add' => (bool) $p->can_add,
                    'edit' => (bool) $p->can_edit,
                ])
                ->toArray();

            return [
                'id' => $u->id,
                'username' => $u->username,
                'name' => $u->name,
                'phone' => $u->phone,
                'business_ids' => $businessIds,
                'permissions' => $perms,
            ];
        });

        return response()->json($rows);
    }

    public function createStaff(Request $request)
    {
        if (!$this->isSuperUser($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['integer', 'exists:businesses,id'],
            'permissions' => ['required', 'array'],
        ]);

        $ownerId = $this->ownerUserId($request);

        $allowedBusinessIds = Business::where('user_id', $ownerId)
            ->whereIn('id', $data['business_ids'])
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if (count($allowedBusinessIds) === 0) {
            return response()->json(['message' => 'No valid business access selected'], 422);
        }

        $permissions = $data['permissions'];

        $staff = DB::transaction(function () use ($data, $ownerId, $allowedBusinessIds, $permissions) {
            $staff = User::create([
                'account_owner_id' => $ownerId,
                'is_super_user' => false,
                'username' => $data['username'],
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'shop_name' => null,
                'settings' => null,
                'password' => Hash::make($data['password']),
            ]);

            foreach ($allowedBusinessIds as $businessId) {
                UserBusinessAccess::create([
                    'account_owner_id' => $ownerId,
                    'user_id' => $staff->id,
                    'business_id' => $businessId,
                ]);
            }

            foreach (self::FEATURES as $feature) {
                $row = $permissions[$feature] ?? [];
                UserFeaturePermission::create([
                    'account_owner_id' => $ownerId,
                    'user_id' => $staff->id,
                    'feature' => $feature,
                    'can_view' => (bool)($row['view'] ?? false),
                    'can_add' => (bool)($row['add'] ?? false),
                    'can_edit' => (bool)($row['edit'] ?? false),
                ]);
            }

            return $staff;
        });

        return response()->json([
            'id' => $staff->id,
            'username' => $staff->username,
            'name' => $staff->name,
            'phone' => $staff->phone,
            'business_ids' => $allowedBusinessIds,
            'permissions' => $permissions,
        ], 201);
    }

    public function meAccess(Request $request)
    {
        return response()->json([
            'is_super_user' => $this->isSuperUser($request),
            'business_ids' => $this->businessIds($request),
            'permissions' => $this->permissionMap($request),
        ]);
    }
}
