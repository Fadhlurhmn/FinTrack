<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\RecurringBillController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::get('/currency/rate', [CurrencyController::class, 'rate']);

    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/reports/by-category', [ReportController::class, 'byCategory']);
    Route::get('/reports/trend', [ReportController::class, 'trend']);

    Route::apiResource('budgets', BudgetController::class);
    Route::get('/budgets/{budget}/status', [BudgetController::class, 'status']);

    Route::apiResource('bills', RecurringBillController::class);
});
