<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function indexAll(Request $request)
    {
        $this->authorizeFeature($request, 'parties', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return Transaction::where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function index(Request $request, Customer $customer)
    {
        $this->authorizeFeature($request, 'parties', 'view');
        if ($customer->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return Transaction::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $this->authorizeFeature($request, 'parties', 'add');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:CREDIT,DEBIT'],
            'note' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'synced' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $this->assertBusinessAccess($request, (int) $data['business_id']);

        $customer = Customer::where('id', $data['customer_id'])
            ->where('user_id', $this->ownerUserId($request))
            ->first();

        if (!$customer) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $createdAt = !empty($data['created_at'])
            ? Carbon::parse($data['created_at'])->setTimeFrom(Carbon::now())
            : now();

        $transaction = Transaction::create([
            'user_id' => $this->ownerUserId($request),
            'business_id' => $data['business_id'],
            'customer_id' => $data['customer_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'attachment_path' => $attachmentPath,
            'synced' => $data['synced'] ?? false,
            'created_at' => $createdAt,
        ]);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorizeFeature($request, 'parties', 'edit');
        if ($transaction->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:CREDIT,DEBIT'],
            'note' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'synced' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $attachmentPath = $transaction->attachment_path;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $updatedCreatedAt = $transaction->created_at;
        if (!empty($data['created_at'])) {
            $updatedCreatedAt = Carbon::parse($data['created_at'])
                ->setTimeFrom(Carbon::now());
        }

        $transaction->update([
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'attachment_path' => $attachmentPath,
            'synced' => $data['synced'] ?? $transaction->synced,
            'created_at' => $updatedCreatedAt,
        ]);

        return response()->json($transaction);
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorizeFeature($request, 'parties', 'edit');
        if ($transaction->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $transaction->markDeleted();
        return response()->json(['message' => 'Deleted']);
    }
}
