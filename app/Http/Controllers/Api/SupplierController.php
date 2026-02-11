<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->query('business_id');

        return Supplier::where('user_id', $request->user()->id)
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->where('is_archived', false)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        $business = Business::where('id', $data['business_id'])
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$business) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $supplier = Supplier::create([
            'user_id' => $request->user()->id,
            'business_id' => $data['business_id'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'is_archived' => false,
        ]);

        return response()->json($supplier, 201);
    }
}
