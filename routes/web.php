<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('dashboard.index'))
  ->middleware('auth')
  ->name('dashboard');
