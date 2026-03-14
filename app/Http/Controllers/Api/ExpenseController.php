<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashbookEntry;
use App\Models\Expense;
use App\Models\ExpenseCatalogItem;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SupplierTransaction;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function categories(Request $request)
    {
        $this->authorizeFeature($request, 'expense', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return ExpenseCategory::where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('name')
            ->get();
    }

    public function storeCategory(Request $request)
    {
        $this->authorizeFeature($request, 'expense', 'add');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = ExpenseCategory::create([
            'user_id' => $this->ownerUserId($request),
            'business_id' => $data['business_id'],
            'name' => $data['name'],
        ]);

        return response()->json($category, 201);
    }

    public function expenseItems(Request $request)
    {
        $this->authorizeFeature($request, 'expense', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return ExpenseCatalogItem::where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('name')
            ->get();
    }

    public function storeExpenseItem(Request $request)
    {
        $this->authorizeFeature($request, 'expense', 'add');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);

        $item = ExpenseCatalogItem::create([
            'user_id' => $this->ownerUserId($request),
            'business_id' => $data['business_id'],
            'name' => $data['name'],
            'rate' => $data['rate'],
        ]);

        return response()->json($item, 201);
    }

    public function updateExpenseItem(Request $request, ExpenseCatalogItem $expenseItem)
    {
        $this->authorizeFeature($request, 'expense', 'edit');
        if ($expenseItem->user_id !== $this->ownerUserId($request)) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);

        $expenseItem->update($data);

        return response()->json($expenseItem);
    }

    public function index(Request $request)
    {
        $this->authorizeFeature($request, 'expense', 'view');
        $businessId = $request->query('business_id');
        if ($businessId) {
            $this->assertBusinessAccess($request, (int) $businessId);
        }

        return Expense::with('items')
            ->where('user_id', $this->ownerUserId($request))
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function show(Request $request, Expense $expense)
    {
        $this->authorizeFeature($request, 'expense', 'view');
        if ($expense->user_id !== $this->ownerUserId($request)) {
            abort(403);
        }

        return $expense->load('items');
    }

    public function store(Request $request)
    {
        $this->authorizeFeature($request, 'expense', 'add');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'expense_number' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'manual_amount' => ['nullable', 'numeric', 'min:0'],
            'apply_tax' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['nullable', 'integer', 'exists:expense_catalog_items,id'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        return $this->persist($request, null, $data, true);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeFeature($request, 'expense', 'edit');
        if ($expense->user_id !== $this->ownerUserId($request)) {
            abort(403);
        }

        $data = $request->validate([
            'expense_number' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'manual_amount' => ['nullable', 'numeric', 'min:0'],
            'apply_tax' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['nullable', 'integer', 'exists:expense_catalog_items,id'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['business_id'] = $expense->business_id;

        return $this->persist($request, $expense, $data, false);
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->authorizeFeature($request, 'expense', 'edit');
        if ($expense->user_id !== $this->ownerUserId($request)) {
            abort(403);
        }

        $expense->markDeleted();

        return response()->json(['message' => 'Expense deleted']);
    }

    private function persist(Request $request, ?Expense $expense, array $data, bool $isCreate)
    {
        $this->assertBusinessAccess($request, (int) $data['business_id']);

        $items = collect($data['items'] ?? []);
        $totalFromItems = $items->sum(function ($i) {
            $qty = (int)($i['qty'] ?? 1);
            $price = (float)($i['price'] ?? 0);
            return $qty * $price;
        });
        $manual = (float)($data['manual_amount'] ?? 0);
        $amount = $items->isNotEmpty() ? $totalFromItems : $manual;
        $applyTax = (bool)($data['apply_tax'] ?? false);
        $vatAmount = $applyTax ? $this->vatFromGrossAmount($amount) : 0.0;

        if ($amount <= 0) {
            return response()->json(['message' => 'Expense amount must be greater than 0'], 422);
        }

        return DB::transaction(function () use ($request, $data, $items, $amount, $manual, $vatAmount, $expense, $isCreate) {
            if ($isCreate) {
                $expense = Expense::create([
                    'user_id' => $this->ownerUserId($request),
                    'business_id' => $data['business_id'],
                    'expense_category_id' => $data['category_id'] ?? null,
                    'expense_number' => $data['expense_number'],
                    'date' => $data['date'],
                    'category_name' => $data['category_name'] ?? null,
                    'manual_amount' => $manual,
                    'amount' => $amount,
                    'vat_amount' => $vatAmount,
                ]);
            } else {
                $expense->update([
                    'expense_category_id' => $data['category_id'] ?? null,
                    'expense_number' => $data['expense_number'],
                    'date' => $data['date'],
                    'category_name' => $data['category_name'] ?? null,
                    'manual_amount' => $manual,
                    'amount' => $amount,
                    'vat_amount' => $vatAmount,
                ]);

                $expense->items()->withDeleted()->update(['del_status' => 'deleted']);
            }

            foreach ($items as $it) {
                $qty = (int)($it['qty'] ?? 1);
                $price = (float)($it['price'] ?? 0);
                ExpenseItem::create([
                    'expense_id' => $expense->id,
                    'expense_catalog_item_id' => $it['item_id'] ?? null,
                    'item_id' => null,
                    'name' => $it['name'] ?? 'Item',
                    'qty' => $qty,
                    'price' => $price,
                    'line_total' => $qty * $price,
                ]);
            }

            $status = $isCreate ? 201 : 200;
            return response()->json($expense->load('items'), $status);
        });
    }

    private function vatFromGrossAmount(float $grossAmount): float
    {
        if ($grossAmount <= 0) {
            return 0.0;
        }

        return round(($grossAmount / 1.05) * 0.05, 2);
    }

    public function cashbook(Request $request)
    {
        $this->authorizeFeature($request, 'bills', 'view');
        $businessId = $request->query('business_id');

        $expenses = Expense::where('user_id', $this->ownerUserId($request))
            ->where('del_status', '!=', 'deleted')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $cashbookEntries = CashbookEntry::where('user_id', $this->ownerUserId($request))
            ->where('del_status', '!=', 'deleted')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $customerPayments = Transaction::where('user_id', $this->ownerUserId($request))
            ->where('del_status', '!=', 'deleted')
            ->where('note', 'like', 'Payment In #%')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $supplierPayments = SupplierTransaction::where('user_id', $this->ownerUserId($request))
            ->where('del_status', '!=', 'deleted')
            ->where('note', 'like', 'Payment Out #%')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $salePayments = Sale::where('user_id', $this->ownerUserId($request))
            ->where('del_status', '!=', 'deleted')
            ->where('payment_mode', '!=', 'unpaid')
            ->where('received_amount', '>', 0)
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $purchasePayments = Purchase::where('user_id', $this->ownerUserId($request))
            ->where('del_status', '!=', 'deleted')
            ->where('payment_mode', '!=', 'unpaid')
            ->where('paid_amount', '>', 0)
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $entries = collect()
            ->concat($expenses->map(function ($e) {
                return [
                    'id' => $e->id,
                    'date' => $e->date,
                    'created_at' => $e->created_at,
                    'label' => 'Expense',
                    'direction' => 'out',
                    'amount' => (float) $e->amount,
                    'payment_mode' => $e->payment_mode ?? 'cash',
                    'note' => $e->note ?? null,
                    'photo_url' => null,
                ];
            }))
            ->concat($cashbookEntries->map(function ($e) {
                return [
                    'id' => $e->id,
                    'date' => $e->date,
                    'created_at' => $e->created_at,
                    'label' => $e->direction === 'in' ? 'Cashbook In' : 'Cashbook Out',
                    'direction' => $e->direction,
                    'amount' => (float) $e->amount,
                    'payment_mode' => $e->payment_mode ?? 'cash',
                    'note' => $e->note,
                    'photo_url' => $e->photo_url,
                ];
            }))
            ->concat($customerPayments->map(function ($t) {
                return [
                    'id' => $t->id,
                    'date' => $t->created_at?->toDateString(),
                    'created_at' => $t->created_at,
                    'label' => 'Payment In',
                    'direction' => 'in',
                    'amount' => (float) $t->amount,
                    'payment_mode' => $t->payment_mode ?? 'cash',
                    'note' => $t->note,
                    'photo_url' => $t->attachment_url,
                ];
            }))
            ->concat($supplierPayments->map(function ($t) {
                return [
                    'id' => $t->id,
                    'date' => $t->created_at?->toDateString(),
                    'created_at' => $t->created_at,
                    'label' => 'Payment Out',
                    'direction' => 'out',
                    'amount' => (float) $t->amount,
                    'payment_mode' => $t->payment_mode ?? 'cash',
                    'note' => $t->note,
                    'photo_url' => $t->attachment_url,
                ];
            }))
            ->concat($salePayments->map(function ($s) {
                return [
                    'id' => $s->id,
                    'date' => $s->date,
                    'created_at' => $s->created_at,
                    'label' => 'Payment In',
                    'direction' => 'in',
                    'amount' => (float) $s->received_amount,
                    'payment_mode' => $s->payment_mode ?? 'cash',
                    'note' => 'Sale Bill #'.$s->bill_number,
                    'photo_url' => null,
                ];
            }))
            ->concat($purchasePayments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'date' => $p->date,
                    'created_at' => $p->created_at,
                    'label' => 'Payment Out',
                    'direction' => 'out',
                    'amount' => (float) $p->paid_amount,
                    'payment_mode' => $p->payment_mode ?? 'cash',
                    'note' => 'Purchase #'.$p->purchase_number,
                    'photo_url' => null,
                ];
            }))
            ->sortByDesc(fn ($e) => $e['date'] . '-' . str_pad((string) $e['id'], 10, '0', STR_PAD_LEFT))
            ->values();

        $today = now()->toDateString();
        $totalIn = $entries->filter(fn ($e) => ($e['direction'] ?? '') === 'in')
            ->sum('amount');
        $totalOut = $entries->filter(fn ($e) => ($e['direction'] ?? '') === 'out')
            ->sum('amount');
        $todayIn = $entries->filter(fn ($e) => $e['date'] === $today && ($e['direction'] ?? '') === 'in')
            ->sum('amount');
        $todayOut = $entries->filter(fn ($e) => $e['date'] === $today && ($e['direction'] ?? '') === 'out')
            ->sum('amount');

        return response()->json([
            'totals' => [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'total_balance' => $totalIn - $totalOut,
                'today_in' => $todayIn,
                'today_out' => $todayOut,
                'today_balance' => $todayIn - $todayOut,
            ],
            'entries' => $entries,
        ]);
    }

    public function storeCashbookEntry(Request $request)
    {
        $this->authorizeFeature($request, 'bills', 'add');
        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'direction' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['nullable', 'in:cash,card,online'],
            'note' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('cashbook', 'public');
        }

        $entry = CashbookEntry::create([
            'user_id' => $this->ownerUserId($request),
            'business_id' => $data['business_id'],
            'direction' => $data['direction'],
            'amount' => $data['amount'],
            'payment_mode' => $data['payment_mode'] ?? 'cash',
            'note' => $data['note'] ?? null,
            'date' => $data['date'],
            'photo_path' => $photoPath,
        ]);

        return response()->json($entry, 201);
    }

    public function destroyExpenseItem(Request $request, ExpenseCatalogItem $expenseItem)
    {
        $this->authorizeFeature($request, 'expense', 'edit');
        if ($expenseItem->user_id !== $this->ownerUserId($request)) {
            abort(403);
        }

        $expenseItem->markDeleted();

        return response()->json(['message' => 'Expense item deleted']);
    }
}
