<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('dashboard.index'))
  ->middleware('auth')
  ->name('dashboard');

Route::middleware('auth')->group(function (): void {
  Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
});

Route::middleware(['auth', 'admin'])->group(function (): void {
  Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update']);
  Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
});
