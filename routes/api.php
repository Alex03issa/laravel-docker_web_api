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


Route::middleware([AuthenticateApiToken::class, 'throttle:20,1'])->group(function () {
    Route::get('/orders', [OrderController::class, 'Orders']);
}); 

Route::middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/sales', [SaleController::class, 'Sales']);
}); 

Route::middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/stocks', [StockController::class, 'Stocks']);
}); 

Route::middleware([AuthenticateApiToken::class])->group(function () {
    Route::get('/incomes', [IncomeController::class, 'Incomes']);
}); 
   
    