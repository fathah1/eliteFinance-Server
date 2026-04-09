<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $this->ownerUserId($request);

        return Business::where('user_id', $ownerId)
            ->where('del_status', '!=', 'deleted')
            ->when(!$this->isSuperUser($request), function ($q) use ($request) {
                $ids = $this->businessIds($request);
                $q->whereIn('id', $ids ?: [0]);
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        if (!$this->isSuperUser($request)) {
            return response()->json(['message' => 'Only super user can create business'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sales_tax_enabled' => ['nullable', 'boolean'],
            'purchase_tax_enabled' => ['nullable', 'boolean'],
            'address_note' => ['nullable', 'string'],
            'trn_no' => ['nullable', 'string'],
        ]);

        $business = Business::create([
            'user_id' => $this->ownerUserId($request),
            'name' => $data['name'],
            'sales_tax_enabled' => (bool) ($data['sales_tax_enabled'] ?? false),
            'purchase_tax_enabled' => (bool) ($data['purchase_tax_enabled'] ?? false),
            'address_note' => $data['address_note'] ?? null,
            'trn_no' => $data['trn_no'] ?? null,
        ]);

        return response()->json($business, 201);
    }

    public function update(Request $request, Business $business)
    {
        if (!$this->isSuperUser($request)) {
            return response()->json(['message' => 'Only super user can update business'], 403);
        }
        if ($business->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sales_tax_enabled' => ['sometimes', 'boolean'],
            'purchase_tax_enabled' => ['sometimes', 'boolean'],
            'address_note' => ['nullable', 'string'],
            'trn_no' => ['nullable', 'string'],
        ]);

        $business->fill($data);
        $business->save();

        return response()->json($business);
    }

    public function destroy(Request $request, Business $business)
    {
        if (!$this->isSuperUser($request)) {
            return response()->json(['message' => 'Only super user can delete business'], 403);
        }
        if ($business->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $business->markDeleted();

        return response()->json(['message' => 'Business deleted']);
    }
}
