<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemStockMovement;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->query('business_id');
        $type = $request->query('type');

        return Item::where('user_id', $request->user()->id)
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->addSelect([
                'last_purchase_price' => ItemStockMovement::select('price')
                    ->whereColumn('item_stock_movements.item_id', 'items.id')
                    ->where('type', 'in')
                    ->orderBy('id', 'desc')
                    ->limit(1),
            ])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'type' => ['required', 'string', 'in:product,service'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'sale_price' => ['nullable', 'numeric'],
            'purchase_price' => ['nullable', 'numeric'],
            'tax_included' => ['nullable', 'boolean'],
            'opening_stock' => ['nullable', 'integer'],
            'low_stock_alert' => ['nullable', 'integer'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('items', 'public');
        }

        $opening = $data['opening_stock'] ?? 0;

        $item = Item::create([
            'user_id' => $request->user()->id,
            'business_id' => $data['business_id'],
            'type' => $data['type'],
            'name' => $data['name'],
            'unit' => $data['unit'],
            'sale_price' => $data['sale_price'] ?? 0,
            'purchase_price' => $data['purchase_price'] ?? 0,
            'tax_included' => $data['tax_included'] ?? true,
            'opening_stock' => $opening,
            'current_stock' => $opening,
            'low_stock_alert' => $data['low_stock_alert'] ?? 0,
            'photo_path' => $photoPath,
        ]);

        return response()->json($item, 201);
    }

    public function update(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'sale_price' => ['nullable', 'numeric'],
            'purchase_price' => ['nullable', 'numeric'],
            'tax_included' => ['nullable', 'boolean'],
            'current_stock' => ['nullable', 'integer'],
            'low_stock_alert' => ['nullable', 'integer'],
            'photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('items', 'public');
        }

        $item->update($data);

        return response()->json($item);
    }

    public function destroy(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function stock(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'type' => ['required', 'string', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric'],
            'date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'sale_bill_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $delta = $data['type'] === 'in' ? $data['quantity'] : -1 * $data['quantity'];
        $item->current_stock = $item->current_stock + $delta;
        if ($data['type'] === 'in' && isset($data['price'])) {
            $item->purchase_price = $data['price'];
        }
        $item->save();

        $movement = ItemStockMovement::create([
            'user_id' => $request->user()->id,
            'business_id' => $item->business_id,
            'item_id' => $item->id,
            'type' => $data['type'],
            'sale_id' => $data['sale_id'] ?? null,
            'sale_bill_number' => $data['sale_bill_number'] ?? null,
            'quantity' => $data['quantity'],
            'price' => $data['price'] ?? 0,
            'date' => $data['date'] ?? now()->toDateString(),
            'note' => $data['note'] ?? null,
        ]);

        return response()->json([
            'item' => $item,
            'movement' => $movement,
        ]);
    }

    public function movements(Request $request, Item $item)
    {
        if ($item->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return ItemStockMovement::where('item_id', $item->id)
            ->where('user_id', $request->user()->id)
            ->orderBy('id', 'desc')
            ->get();
    }
}
