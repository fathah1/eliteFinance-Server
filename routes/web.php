<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogViewerController;

Route::get('/', [LogViewerController::class, 'index']);
Route::get('/logs/tail', [LogViewerController::class, 'tail']);
Route::post('/logs/clear', [LogViewerController::class, 'clear']);
