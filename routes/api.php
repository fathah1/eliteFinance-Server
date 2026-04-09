<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupplierTransactionController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\UserManagementController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me/access', [UserManagementController::class, 'meAccess']);
    Route::get('/users/staff', [UserManagementController::class, 'staff']);
    Route::post('/users/staff', [UserManagementController::class, 'createStaff']);

    Route::get('/businesses', [BusinessController::class, 'index']);
    Route::post('/businesses', [BusinessController::class, 'store']);
    Route::put('/businesses/{business}', [BusinessController::class, 'update']);
    Route::patch('/businesses/{business}', [BusinessController::class, 'update']);
    Route::delete('/businesses/{business}', [BusinessController::class, 'destroy']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);

    Route::get('/transactions', [TransactionController::class, 'indexAll']);
    Route::get('/customers/{customer}/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy']);

    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

    Route::get('/supplier-transactions', [SupplierTransactionController::class, 'indexAll']);
    Route::get('/suppliers/{supplier}/transactions', [SupplierTransactionController::class, 'index']);
    Route::post('/supplier-transactions', [SupplierTransactionController::class, 'store']);
    Route::put('/supplier-transactions/{supplierTransaction}', [SupplierTransactionController::class, 'update']);
    Route::delete('/supplier-transactions/{supplierTransaction}', [SupplierTransactionController::class, 'destroy']);

    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::put('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
    Route::post('/items/{item}/stock', [ItemController::class, 'stock']);
    Route::get('/items/{item}/movements', [ItemController::class, 'movements']);

    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales/returns', [SaleController::class, 'returnIndex']);
    Route::post('/sales/returns', [SaleController::class, 'storeReturn']);
    Route::delete('/sales/returns/{saleReturn}', [SaleController::class, 'destroyReturn']);
    Route::post('/sales/payments', [SaleController::class, 'storePayment']);
    Route::put('/sales/payments/{transaction}', [SaleController::class, 'updatePayment']);
    Route::delete('/sales/{sale}', [SaleController::class, 'destroy']);

    Route::get('/purchases', [PurchaseController::class, 'index']);
    Route::post('/purchases', [PurchaseController::class, 'store']);
    Route::get('/purchases/returns', [PurchaseController::class, 'returnIndex']);
    Route::post('/purchases/returns', [PurchaseController::class, 'storeReturn']);
    Route::delete('/purchases/returns/{purchaseReturn}', [PurchaseController::class, 'destroyReturn']);
    Route::post('/purchases/payments', [PurchaseController::class, 'storePayment']);
    Route::put('/purchases/payments/{supplierTransaction}', [PurchaseController::class, 'updatePayment']);
    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy']);

    Route::get('/expense-categories', [ExpenseController::class, 'categories']);
    Route::post('/expense-categories', [ExpenseController::class, 'storeCategory']);

    Route::get('/expense-items', [ExpenseController::class, 'expenseItems']);
    Route::post('/expense-items', [ExpenseController::class, 'storeExpenseItem']);
    Route::put('/expense-items/{expenseItem}', [ExpenseController::class, 'updateExpenseItem']);
    Route::delete('/expense-items/{expenseItem}', [ExpenseController::class, 'destroyExpenseItem']);

    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

    Route::get('/cashbook', [ExpenseController::class, 'cashbook']);
    Route::post('/cashbook/entries', [ExpenseController::class, 'storeCashbookEntry']);
});
