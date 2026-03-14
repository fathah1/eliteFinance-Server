<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemStockMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeFeature($request, 'purchase', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return Purchase::with('items')
            ->where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function returnIndex(Request $request)
    {
        $this->authorizeFeature($request, 'purchase', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return PurchaseReturn::with('items')
            ->where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $this->authorizeFeature($request, 'purchase', 'add');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'purchase_number' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'party_name' => ['nullable', 'string', 'max:255'],
            'party_phone' => ['nullable', 'string', 'max:50'],
            'payment_mode' => ['required', 'string', 'in:unpaid,cash,card'],
            'due_date' => ['nullable', 'date'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'private_notes' => ['nullable', 'string'],
            'manual_amount' => ['nullable', 'numeric', 'min:0'],
            'line_items' => ['nullable', 'string'],
            'additional_charges' => ['nullable', 'string'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:aed,percent'],
            'discount_label' => ['nullable', 'string', 'max:100'],
            'note_photos' => ['nullable', 'array'],
            'note_photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->assertBusinessAccess($request, (int) $data['business_id']);

        $lineItems = collect(json_decode($data['line_items'] ?? '[]', true) ?: []);
        $charges = collect(json_decode($data['additional_charges'] ?? '[]', true) ?: []);

        if ($lineItems->isEmpty() && (($data['manual_amount'] ?? 0) <= 0)) {
            return response()->json(['message' => 'Either line items or amount is required'], 422);
        }

        if (!empty($data['supplier_id'])) {
            $exists = Supplier::where('id', $data['supplier_id'])
                ->where('user_id', $this->ownerUserId($request))
                ->exists();
            if (!$exists) {
                return response()->json(['message' => 'Invalid supplier'], 422);
            }
        }

        return DB::transaction(function () use ($request, $data, $lineItems, $charges) {
            $subtotal = $lineItems->isNotEmpty()
                ? $lineItems->sum(fn ($i) => (float)($i['price'] ?? 0) * (int)($i['qty'] ?? 0))
                : (float)($data['manual_amount'] ?? 0);

            $additionalTotal = $charges->sum(fn ($c) => (float)($c['amount'] ?? 0));
            $discountType = $data['discount_type'] ?? 'aed';
            $discountValue = (float)($data['discount_value'] ?? 0);
            $beforeDiscount = $subtotal + $additionalTotal;
            $discountAmount = $discountType === 'percent'
                ? ($beforeDiscount * max(0, min($discountValue, 100)) / 100)
                : $discountValue;
            $total = max(0, $beforeDiscount - $discountAmount);
            $vatAmount = $this->vatFromGrossAmount($total);

            $paidInput = (float)($data['paid_amount'] ?? 0);
            $paid = $data['payment_mode'] === 'unpaid' ? 0 : max(0, min($paidInput, $total));
            $balanceDue = max(0, $total - $paid);
            $paymentStatus = $balanceDue > 0 ? 'unpaid' : 'fully_paid';

            $photos = [];
            if ($request->hasFile('note_photos')) {
                foreach ($request->file('note_photos') as $photo) {
                    $photos[] = $photo->store('purchase_notes', 'public');
                }
            }

            $purchase = Purchase::create([
                'user_id' => $this->ownerUserId($request),
                'business_id' => $data['business_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_number' => $data['purchase_number'],
                'date' => $data['date'],
                'party_name' => $data['party_name'] ?? null,
                'party_phone' => $data['party_phone'] ?? null,
                'manual_amount' => $data['manual_amount'] ?? 0,
                'subtotal' => $subtotal,
                'additional_charges_total' => $additionalTotal,
                'discount_value' => $discountValue,
                'discount_type' => $discountType,
                'discount_label' => $data['discount_label'] ?? null,
                'discount_amount' => $discountAmount,
                'total_amount' => $total,
                'vat_amount' => $vatAmount,
                'payment_mode' => $data['payment_mode'],
                'paid_amount' => $paid,
                'balance_due' => $balanceDue,
                'payment_status' => $paymentStatus,
                'due_date' => $data['due_date'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'private_notes' => $data['private_notes'] ?? null,
                'note_photos' => $photos,
            ]);

            foreach ($lineItems as $line) {
                $itemId = $line['item_id'] ?? null;
                $qty = (int)($line['qty'] ?? 0);
                $price = (float)($line['price'] ?? 0);
                $name = (string)($line['name'] ?? '');
                if ($qty <= 0) {
                    continue;
                }

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'item_id' => $itemId,
                    'name' => $name,
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $qty * $price,
                ]);

                if ($itemId) {
                    $item = Item::where('id', $itemId)
                        ->where('user_id', $this->ownerUserId($request))
                        ->where('business_id', $data['business_id'])
                        ->first();

                    if ($item) {
                        $item->current_stock = $item->current_stock + $qty;
                        if ($price > 0) {
                            $item->purchase_price = $price;
                        }
                        $item->save();

                        ItemStockMovement::create([
                            'user_id' => $this->ownerUserId($request),
                            'business_id' => $item->business_id,
                            'item_id' => $item->id,
                            'purchase_id' => $purchase->id,
                            'purchase_number' => $purchase->purchase_number,
                            'type' => 'in',
                            'quantity' => $qty,
                            'price' => $price,
                            'date' => $purchase->date,
                            'note' => 'Purchase #'.$purchase->purchase_number,
                        ]);
                    }
                }
            }

            if (!empty($data['supplier_id'])) {
                $createdAt = $this->withTime($purchase->date);
                SupplierTransaction::create([
                    'user_id' => $this->ownerUserId($request),
                    'business_id' => $data['business_id'],
                    'supplier_id' => $data['supplier_id'],
                    'amount' => $total,
                    'type' => 'DEBIT',
                    'note' => 'Purchase #'.$purchase->purchase_number,
                    'synced' => true,
                    'created_at' => $createdAt,
                ]);

                if ($paid > 0) {
                    $createdAtPaid = $this->withTime($purchase->date);
                    SupplierTransaction::create([
                        'user_id' => $this->ownerUserId($request),
                        'business_id' => $data['business_id'],
                        'supplier_id' => $data['supplier_id'],
                        'amount' => $paid,
                        'type' => 'CREDIT',
                        'note' => 'Payment Out #'.$purchase->purchase_number,
                        'synced' => true,
                        'created_at' => $createdAtPaid,
                    ]);
                }
            }

            return response()->json($purchase->load('items'), 201);
        });
    }

    public function storeReturn(Request $request)
    {
        $this->authorizeFeature($request, 'purchase', 'edit');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'return_number' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'purchase_id' => ['nullable', 'integer', 'exists:purchases,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'settlement_mode' => ['required', 'string', 'in:credit_party,cash,card'],
            'manual_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertBusinessAccess($request, (int) $data['business_id']);

        return DB::transaction(function () use ($request, $data) {
            $purchase = null;
            if (!empty($data['purchase_id'])) {
                $purchase = Purchase::with('items')
                    ->where('id', $data['purchase_id'])
                    ->where('user_id', $this->ownerUserId($request))
                    ->where('business_id', $data['business_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$purchase) {
                    return response()->json(['message' => 'Purchase not found'], 404);
                }
            }

            $supplierId = $data['supplier_id'] ?? $purchase?->supplier_id;
            $items = collect($data['items'] ?? []);

            if ($items->isEmpty() && $purchase && $purchase->items->isNotEmpty()) {
                $items = $purchase->items->map(function (PurchaseItem $it) {
                    return [
                        'item_id' => $it->item_id,
                        'name' => $it->name,
                        'qty' => (int) $it->qty,
                        'price' => (float) $it->price,
                    ];
                });
            }

            $amount = $items->sum(function ($i) {
                return ((int)($i['qty'] ?? 0)) * ((float)($i['price'] ?? 0));
            });
            if ($amount <= 0) {
                $amount = (float)($data['manual_amount'] ?? 0);
            }
            if ($amount <= 0) {
                return response()->json(['message' => 'Return amount must be greater than 0'], 422);
            }

            $modeText = match ($data['settlement_mode']) {
                'credit_party' => 'Credit from supplier',
                'cash' => 'Refund received (cash)',
                default => 'Refund received (card)',
            };

            $purchaseReturn = PurchaseReturn::create([
                'user_id' => $this->ownerUserId($request),
                'business_id' => $data['business_id'],
                'purchase_id' => $purchase?->id,
                'supplier_id' => $supplierId,
                'return_number' => $data['return_number'],
                'date' => $data['date'],
                'settlement_mode' => $data['settlement_mode'],
                'total_amount' => $amount,
                'note' => 'Purchase Return #'.$data['return_number'].($purchase ? ' for Purchase #'.$purchase->purchase_number : '').' - '.$modeText,
            ]);

            foreach ($items as $it) {
                $itemId = $it['item_id'] ?? null;
                $qty = (int)($it['qty'] ?? 0);
                $price = (float)($it['price'] ?? 0);
                $name = (string)($it['name'] ?? 'Item');

                if ($qty <= 0) {
                    continue;
                }

                PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'item_id' => $itemId,
                    'name' => $name,
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $qty * $price,
                ]);

                if (!$itemId) {
                    continue;
                }

                $item = Item::where('id', $itemId)
                    ->where('user_id', $this->ownerUserId($request))
                    ->where('business_id', $data['business_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$item) {
                    continue;
                }

                $item->current_stock = $item->current_stock - $qty;
                $item->save();

                ItemStockMovement::create([
                    'user_id' => $this->ownerUserId($request),
                    'business_id' => $item->business_id,
                    'item_id' => $item->id,
                    'purchase_id' => $purchase?->id,
                    'purchase_number' => $purchase?->purchase_number,
                    'type' => 'out',
                    'quantity' => $qty,
                    'price' => $price,
                    'date' => $data['date'],
                    'note' => 'Purchase Return #'.$data['return_number'],
                ]);
            }

            if ($purchase) {
                $newTotal = max(0, (float)$purchase->total_amount - $amount);
                $newPaid = (float)$purchase->paid_amount;
                if (($data['settlement_mode'] ?? '') !== 'credit_party') {
                    $newPaid = max(0, $newPaid - $amount);
                }
                $newBalance = max(0, $newTotal - $newPaid);

                $purchase->update([
                    'total_amount' => $newTotal,
                    'paid_amount' => $newPaid,
                    'balance_due' => $newBalance,
                    'payment_status' => $newBalance > 0 ? 'unpaid' : 'fully_paid',
                ]);
            }

            if (!empty($supplierId)) {
                $createdAt = $this->withTime($data['date']);
                SupplierTransaction::create([
                    'user_id' => $this->ownerUserId($request),
                    'business_id' => $data['business_id'],
                    'supplier_id' => $supplierId,
                    'amount' => $amount,
                    'type' => 'CREDIT',
                    'note' => $purchaseReturn->note,
                    'synced' => true,
                    'created_at' => $createdAt,
                ]);
            }

            return response()->json($purchaseReturn->load('items'), 201);
        });
    }

    public function storePayment(Request $request)
    {
        $this->authorizeFeature($request, 'purchase', 'edit');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'payment_number' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'string', 'in:cash,card'],
            'note' => ['nullable', 'string'],
            'purchase_ids' => ['nullable', 'array'],
            'purchase_ids.*' => ['integer', 'exists:purchases,id'],
        ]);

        $this->assertBusinessAccess($request, (int) $data['business_id']);

        $supplier = Supplier::where('id', $data['supplier_id'])
            ->where('user_id', $this->ownerUserId($request))
            ->first();
        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        return DB::transaction(function () use ($request, $data) {
            $remaining = (float) $data['amount'];
            $purchaseIds = collect($data['purchase_ids'] ?? [])->map(fn ($v) => (int) $v)->filter()->values();

            $purchases = Purchase::where('user_id', $this->ownerUserId($request))
                ->where('business_id', $data['business_id'])
                ->where('supplier_id', $data['supplier_id'])
                ->when($purchaseIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $purchaseIds->all()))
                ->orderBy('date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $allocations = [];
            $appliedTotal = 0.0;

            foreach ($purchases as $purchase) {
                if ($remaining <= 0) {
                    break;
                }

                $pending = max(0, (float)$purchase->total_amount - (float)$purchase->paid_amount);
                if ($pending <= 0) {
                    continue;
                }

                $apply = min($pending, $remaining);
                $newPaid = (float)$purchase->paid_amount + $apply;
                $newBalance = max(0, (float)$purchase->total_amount - $newPaid);

                $purchase->update([
                    'paid_amount' => $newPaid,
                    'balance_due' => $newBalance,
                    'payment_status' => $newBalance > 0 ? 'unpaid' : 'fully_paid',
                ]);

                $allocations[] = [
                    'purchase_id' => $purchase->id,
                    'purchase_number' => $purchase->purchase_number,
                    'applied_amount' => round($apply, 2),
                ];
                $appliedTotal += $apply;
                $remaining -= $apply;
            }

            if ($appliedTotal <= 0) {
                return response()->json(['message' => 'No pending purchases found for this payment'], 422);
            }

            $modeText = $data['payment_mode'] === 'card' ? 'Card' : 'Cash';
            $note = 'Payment Out #'.$data['payment_number'].' ('.$modeText.')';
            if (!empty($data['note'])) {
                $note .= ' - '.trim((string)$data['note']);
            }

            $linkedPurchaseIds = collect($allocations)
                ->pluck('purchase_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $createdAt = $this->withTime($data['date']);
            SupplierTransaction::create([
                'user_id' => $this->ownerUserId($request),
                'business_id' => $data['business_id'],
                'supplier_id' => $data['supplier_id'],
                'amount' => $appliedTotal,
                'type' => 'CREDIT',
                'payment_number' => $data['payment_number'],
                'payment_mode' => $data['payment_mode'],
                'purchase_ids' => $linkedPurchaseIds,
                'allocations' => $allocations,
                'note' => $note,
                'synced' => true,
                'created_at' => $createdAt,
            ]);

            return response()->json([
                'message' => 'Payment saved',
                'payment_number' => (int)$data['payment_number'],
                'applied_amount' => round($appliedTotal, 2),
                'unapplied_amount' => round(max(0, $remaining), 2),
                'allocations' => $allocations,
            ], 201);
        });
    }

    public function destroyReturn(Request $request, PurchaseReturn $purchaseReturn)
    {
        $this->authorizeFeature($request, 'purchase', 'edit');
        if ($purchaseReturn->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $purchaseReturn->markDeleted();
        $purchaseReturn->items()->withDeleted()->update(['del_status' => 'deleted']);

        return response()->json(['message' => 'Purchase return deleted']);
    }

    private function vatFromGrossAmount(float $grossAmount): float
    {
        if ($grossAmount <= 0) {
            return 0.0;
        }

        return round(($grossAmount / 1.05) * 0.05, 2);
    }

    private function withTime($date): string
    {
        $base = Carbon::parse($date);
        return $base->setTimeFrom(Carbon::now())->toDateTimeString();
    }

    public function destroy(Request $request, Purchase $purchase)
    {
        $this->authorizeFeature($request, 'purchase', 'edit');
        if ($purchase->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $purchase->markDeleted();
        $purchase->items()->withDeleted()->update(['del_status' => 'deleted']);

        return response()->json(['message' => 'Purchase deleted']);
    }

    public function updatePayment(Request $request, SupplierTransaction $supplierTransaction)
    {
        $this->authorizeFeature($request, 'purchase', 'edit');
        if ($supplierTransaction->user_id !== $this->ownerUserId($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'payment_number' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'string', 'in:cash,card'],
            'note' => ['nullable', 'string'],
            'purchase_ids' => ['nullable', 'array'],
            'purchase_ids.*' => ['integer'],
        ]);

        $this->assertBusinessAccess($request, (int) $data['business_id']);

        $supplier = Supplier::where('id', $data['supplier_id'])
            ->where('user_id', $this->ownerUserId($request))
            ->first();
        if (!$supplier) {
            return response()->json(['message' => 'Invalid supplier'], 422);
        }

        return DB::transaction(function () use ($request, $data, $supplierTransaction) {
            $prevAllocations = collect($supplierTransaction->allocations ?? []);
            foreach ($prevAllocations as $alloc) {
                $purchaseId = (int) ($alloc['purchase_id'] ?? 0);
                $applied = (float) ($alloc['applied_amount'] ?? 0);
                if ($purchaseId <= 0 || $applied <= 0) {
                    continue;
                }
                $purchase = Purchase::where('id', $purchaseId)
                    ->where('user_id', $this->ownerUserId($request))
                    ->where('business_id', $data['business_id'])
                    ->lockForUpdate()
                    ->first();
                if (!$purchase) {
                    continue;
                }
                $newPaid = max(0, (float)$purchase->paid_amount - $applied);
                $newBalance = max(0, (float)$purchase->total_amount - $newPaid);
                $purchase->update([
                    'paid_amount' => $newPaid,
                    'balance_due' => $newBalance,
                    'payment_status' => $newBalance > 0 ? 'unpaid' : 'fully_paid',
                ]);
            }

            $remaining = (float) $data['amount'];
            $purchaseIds = collect($data['purchase_ids'] ?? [])
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->values();

            $purchases = Purchase::where('user_id', $this->ownerUserId($request))
                ->where('business_id', $data['business_id'])
                ->where('supplier_id', $data['supplier_id'])
                ->when($purchaseIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $purchaseIds->all()))
                ->orderBy('date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $allocations = [];
            $appliedTotal = 0.0;

            foreach ($purchases as $purchase) {
                if ($remaining <= 0) {
                    break;
                }
                $pending = max(0, (float)$purchase->total_amount - (float)$purchase->paid_amount);
                if ($pending <= 0) {
                    continue;
                }
                $apply = min($pending, $remaining);
                $newPaid = (float)$purchase->paid_amount + $apply;
                $newBalance = max(0, (float)$purchase->total_amount - $newPaid);

                $purchase->update([
                    'paid_amount' => $newPaid,
                    'balance_due' => $newBalance,
                    'payment_status' => $newBalance > 0 ? 'unpaid' : 'fully_paid',
                ]);

                $allocations[] = [
                    'purchase_id' => $purchase->id,
                    'purchase_number' => $purchase->purchase_number,
                    'applied_amount' => round($apply, 2),
                ];
                $appliedTotal += $apply;
                $remaining -= $apply;
            }

            if ($appliedTotal <= 0) {
                return response()->json(['message' => 'No pending purchases found for this payment'], 422);
            }

            $modeText = $data['payment_mode'] === 'card' ? 'Card' : 'Cash';
            $note = 'Payment Out #'.$data['payment_number'].' ('.$modeText.')';
            if (!empty($data['note'])) {
                $note .= ' - '.trim((string)$data['note']);
            }

            $linkedPurchaseIds = collect($allocations)
                ->pluck('purchase_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $supplierTransaction->update([
                'business_id' => $data['business_id'],
                'supplier_id' => $data['supplier_id'],
                'amount' => $appliedTotal,
                'payment_number' => $data['payment_number'],
                'payment_mode' => $data['payment_mode'],
                'purchase_ids' => $linkedPurchaseIds,
                'allocations' => $allocations,
                'note' => $note,
                'created_at' => $this->withTime($data['date']),
            ]);

            return response()->json([
                'message' => 'Payment updated',
                'payment_number' => (int)$data['payment_number'],
                'applied_amount' => round($appliedTotal, 2),
                'unapplied_amount' => round(max(0, $remaining), 2),
                'allocations' => $allocations,
            ]);
        });
    }
}
