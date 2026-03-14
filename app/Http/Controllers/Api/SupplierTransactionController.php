<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SupplierTransactionController extends Controller
{
    public function indexAll(Request $request)
    {
        $this->authorizeFeature($request, 'parties', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return SupplierTransaction::where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function index(Request $request, Supplier $supplier)
    {
        $this->authorizeFeature($request, 'parties', 'view');
        if ($supplier->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return SupplierTransaction::where('supplier_id', $supplier->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $this->authorizeFeature($request, 'parties', 'add');
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

        $this->assertBusinessAccess($request, (int) $data['business_id']);

        $supplier = Supplier::where('id', $data['supplier_id'])
            ->where('user_id', $this->ownerUserId($request))
            ->first();
        if (!$supplier) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments', 'public');
        }

        $createdAt = !empty($data['created_at'])
            ? Carbon::parse($data['created_at'])->setTimeFrom(Carbon::now())
            : now();

        $transaction = SupplierTransaction::create([
            'user_id' => $this->ownerUserId($request),
            'business_id' => $data['business_id'],
            'supplier_id' => $data['supplier_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'attachment_path' => $attachmentPath,
            'synced' => $data['synced'] ?? false,
            'created_at' => $createdAt,
        ]);

        return response()->json($transaction, 201);
    }

    public function update(Request $request, SupplierTransaction $supplierTransaction)
    {
        $this->authorizeFeature($request, 'parties', 'edit');
        if ($supplierTransaction->user_id !== $this->ownerUserId($request)) {
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

        $updatedCreatedAt = $supplierTransaction->created_at;
        if (!empty($data['created_at'])) {
            $updatedCreatedAt = Carbon::parse($data['created_at'])
                ->setTimeFrom(Carbon::now());
        }

        $supplierTransaction->update([
            'amount' => $data['amount'],
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'attachment_path' => $attachmentPath,
            'synced' => $data['synced'] ?? $supplierTransaction->synced,
            'created_at' => $updatedCreatedAt,
        ]);

        return response()->json($supplierTransaction);
    }

    public function destroy(Request $request, SupplierTransaction $supplierTransaction)
    {
        $this->authorizeFeature($request, 'parties', 'edit');
        if ($supplierTransaction->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $supplierTransaction->markDeleted();
        return response()->json(['message' => 'Deleted']);
    }
}
