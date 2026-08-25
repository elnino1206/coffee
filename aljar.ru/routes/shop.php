<?php

use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('cart', [CartController::class, 'show'])->name('cart.show');
Route::post('cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('cart/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('checkout/done', [CheckoutController::class, 'done'])->name('checkout.done');
