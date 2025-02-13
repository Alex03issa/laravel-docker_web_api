<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\IncomeController;
use App\Http\Middleware\AuthenticateApiToken;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth.api', 'throttle:20,1'])->group(function () {
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/stocks', [StockController::class, 'index']);
    Route::get('/incomes', [IncomeController::class, 'index']);
});



Route::middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/local-orders', [OrderController::class, 'localOrders']);
}); 

Route::middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/local-sales', [SaleController::class, 'localSales']);
}); 

Route::middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/local-stocks', [StockController::class, 'localStocks']);
}); 

Route::middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/local-incomes', [IncomeController::class, 'localIncomes']);
}); 
   
    