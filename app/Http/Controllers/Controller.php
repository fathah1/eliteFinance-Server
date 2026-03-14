<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\UserBusinessAccess;
use App\Models\UserFeaturePermission;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function ownerUserId(Request $request): int
    {
        $user = $request->user();
        return (int) ($user->account_owner_id ?: $user->id);
    }

    protected function isSuperUser(Request $request): bool
    {
        return (bool) $request->user()->is_super_user;
    }

    protected function businessIds(Request $request): array
    {
        $ownerId = $this->ownerUserId($request);

        if ($this->isSuperUser($request)) {
            return Business::where('user_id', $ownerId)->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        return UserBusinessAccess::where('account_owner_id', $ownerId)
            ->where('user_id', $request->user()->id)
            ->pluck('business_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    protected function assertBusinessAccess(Request $request, int $businessId): void
    {
        $allowed = $this->businessIds($request);
        if (!in_array($businessId, $allowed, true)) {
            abort(403, 'Business access denied');
        }
    }



    protected function authorizeFeature(Request $request, string $feature, string $action): void
    {
        if ($this->isSuperUser($request)) {
            return;
        }

        $map = $this->permissionMap($request);
        $row = $map[$feature] ?? ['view' => false, 'add' => false, 'edit' => false];
        if (!($row[$action] ?? false)) {
            abort(403, 'Permission denied');
        }
    }

    protected function permissionMap(Request $request): array
    {
        $all = ['parties', 'items', 'reports', 'sale', 'purchase', 'expense', 'bills'];

        if ($this->isSuperUser($request)) {
            $full = [];
            foreach ($all as $feature) {
                $full[$feature] = ['view' => true, 'add' => true, 'edit' => true];
            }
            return $full;
        }

        $rows = UserFeaturePermission::where('account_owner_id', $this->ownerUserId($request))
            ->where('user_id', $request->user()->id)
            ->get();

        $map = [];
        foreach ($all as $feature) {
            $map[$feature] = ['view' => false, 'add' => false, 'edit' => false];
        }
        foreach ($rows as $row) {
            $map[$row->feature] = [
                'view' => (bool) $row->can_view,
                'add' => (bool) $row->can_add,
                'edit' => (bool) $row->can_edit,
            ];
        }

        return $map;
    }
}
