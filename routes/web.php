<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products');

Route::middleware('auth')->group(function (): void {
  Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
  Route::resource('suppliers', SupplierController::class)->only(['index', 'create', 'store', 'edit', 'update']);
  Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
  Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'edit', 'update']);
  Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
  Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
  Route::post('purchases/{purchase}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.confirm');
  Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
  Route::get('stocks/{product}/movements', [StockController::class, 'movements'])->name('stocks.movements');
});

Route::middleware(['auth', 'admin'])->group(function (): void {
  Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update']);
  Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
});
