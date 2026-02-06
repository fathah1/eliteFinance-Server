<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function indexAll(Request $request)
    {
        return Transaction::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function index(Request $request, Customer $customer)
    {
        if ($customer->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return Transaction::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:CREDIT,DEBIT'],
            'note' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'synced' => ['nullable', 'boolean'],
        ]);

        $customer = Customer::where('id', $data['customer_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$customer) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'customer_id' => $data['customer_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'synced' => $data['synced'] ?? false,
            'created_at' => $data['created_at'] ?? now(),
        ]);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:CREDIT,DEBIT'],
            'note' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'synced' => ['nullable', 'boolean'],
        ]);

        $transaction->update([
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'synced' => $data['synced'] ?? $transaction->synced,
            'created_at' => $data['created_at'] ?? $transaction->created_at,
        ]);

        return response()->json($transaction);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $transaction->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
