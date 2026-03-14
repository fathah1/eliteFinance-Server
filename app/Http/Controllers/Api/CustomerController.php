<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeFeature($request, 'parties', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return Customer::where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->where('is_archived', false)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $this->authorizeFeature($request, 'parties', 'add');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'opening_balance' => ['nullable', 'numeric'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->assertBusinessAccess($request, (int) $data['business_id']);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('parties/customers', 'public');
        }

        $customer = Customer::create([
            'user_id' => $this->ownerUserId($request),
            'business_id' => $data['business_id'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'is_archived' => false,
            'photo_path' => $photoPath,
        ]);

        return response()->json($customer, 201);
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeFeature($request, 'parties', 'edit');
        if ($customer->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ];

        if ($request->hasFile('photo')) {
            $payload['photo_path'] = $request->file('photo')->store('parties/customers', 'public');
        }

        $customer->update($payload);

        return response()->json($customer->fresh());
    }

    public function destroy(Request $request, Customer $customer)
    {
        $this->authorizeFeature($request, 'parties', 'edit');
        if ($customer->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $customer->transactions()->withDeleted()->update(['del_status' => 'deleted']);
        $customer->markDeleted();

        return response()->json(['message' => 'Customer deleted']);
    }
}
