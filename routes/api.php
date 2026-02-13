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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/businesses', [BusinessController::class, 'index']);
    Route::post('/businesses', [BusinessController::class, 'store']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);

    Route::get('/transactions', [TransactionController::class, 'indexAll']);
    Route::get('/customers/{customer}/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy']);

    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);

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
});
