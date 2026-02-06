<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        return Customer::where('user_id', $request->user()->id)
            ->where('is_archived', false)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        $customer = Customer::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'is_archived' => false,
        ]);

        return response()->json($customer, 201);
    }
}
