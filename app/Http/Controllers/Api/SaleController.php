<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemStockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->query('business_id');

        return Sale::with('items')
            ->where('user_id', $request->user()->id)
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'bill_number' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'party_name' => ['nullable', 'string', 'max:255'],
            'party_phone' => ['nullable', 'string', 'max:50'],
            'payment_mode' => ['required', 'string', 'in:unpaid,cash,card'],
            'due_date' => ['nullable', 'date'],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
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

        $lineItems = collect(json_decode($data['line_items'] ?? '[]', true) ?: []);
        $charges = collect(json_decode($data['additional_charges'] ?? '[]', true) ?: []);

        if ($lineItems->isEmpty() && (($data['manual_amount'] ?? 0) <= 0)) {
            return response()->json(['message' => 'Either line items or amount is required'], 422);
        }

        if (!empty($data['customer_id'])) {
            $customerExists = Customer::where('id', $data['customer_id'])
                ->where('user_id', $request->user()->id)
                ->exists();
            if (!$customerExists) {
                return response()->json(['message' => 'Invalid customer'], 422);
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

            $receivedInput = (float)($data['received_amount'] ?? 0);
            $received = $data['payment_mode'] === 'unpaid' ? 0 : max(0, min($receivedInput, $total));
            $balanceDue = max(0, $total - $received);
            $paymentStatus = $balanceDue > 0 ? 'unpaid' : 'fully_paid';

            $photos = [];
            if ($request->hasFile('note_photos')) {
                foreach ($request->file('note_photos') as $photo) {
                    $photos[] = $photo->store('sales_notes', 'public');
                }
            }

            $sale = Sale::create([
                'user_id' => $request->user()->id,
                'business_id' => $data['business_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'bill_number' => $data['bill_number'],
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
                'payment_mode' => $data['payment_mode'],
                'received_amount' => $received,
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

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_id' => $itemId,
                    'name' => $name,
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $qty * $price,
                ]);

                if ($itemId) {
                    $item = Item::where('id', $itemId)
                        ->where('user_id', $request->user()->id)
                        ->where('business_id', $data['business_id'])
                        ->first();

                    if ($item) {
                        $item->current_stock = $item->current_stock - $qty;
                        $item->save();

                        ItemStockMovement::create([
                            'user_id' => $request->user()->id,
                            'business_id' => $item->business_id,
                            'item_id' => $item->id,
                            'sale_id' => $sale->id,
                            'sale_bill_number' => $sale->bill_number,
                            'type' => 'out',
                            'quantity' => $qty,
                            'price' => $price,
                            'date' => $sale->date,
                            'note' => 'Sale Bill #'.$sale->bill_number,
                        ]);
                    }
                }
            }

            if (!empty($data['customer_id']) && $balanceDue > 0) {
                Transaction::create([
                    'user_id' => $request->user()->id,
                    'business_id' => $data['business_id'],
                    'customer_id' => $data['customer_id'],
                    'amount' => $balanceDue,
                    'type' => 'CREDIT',
                    'note' => 'Sale Bill #'.$sale->bill_number,
                    'synced' => true,
                    'created_at' => $sale->date,
                ]);
            }

            return response()->json($sale->load('items'), 201);
        });
    }
}
