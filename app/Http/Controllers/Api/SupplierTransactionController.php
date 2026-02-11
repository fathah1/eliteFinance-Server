<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Illuminate\Http\Request;

class SupplierTransactionController extends Controller
{
    public function indexAll(Request $request)
    {
        $businessId = $request->query('business_id');

        return SupplierTransaction::where('user_id', $request->user()->id)
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function index(Request $request, Supplier $supplier)
    {
        if ($supplier->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return SupplierTransaction::where('supplier_id', $supplier->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:CREDIT,DEBIT'],
            'note' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'synced' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $business = Business::where('id', $data['business_id'])
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$business) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $supplier = Supplier::where('id', $data['supplier_id'])
            ->where('user_id', $request->user()->id)
            ->first();
        if (!$supplier) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $transaction = SupplierTransaction::create([
            'user_id' => $request->user()->id,
            'business_id' => $data['business_id'],
            'supplier_id' => $data['supplier_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'attachment_path' => $attachmentPath,
            'synced' => $data['synced'] ?? false,
            'created_at' => $data['created_at'] ?? now(),
        ]);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, SupplierTransaction $supplierTransaction)
    {
        if ($supplierTransaction->user_id !== $request->user()->id) {
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

        $attachmentPath = $supplierTransaction->attachment_path;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $supplierTransaction->update([
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'attachment_path' => $attachmentPath,
            'synced' => $data['synced'] ?? $supplierTransaction->synced,
            'created_at' => $data['created_at'] ?? $supplierTransaction->created_at,
        ]);

        return response()->json($supplierTransaction);
    }

    public function destroy(Request $request, SupplierTransaction $supplierTransaction)
    {
        if ($supplierTransaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $supplierTransaction->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
