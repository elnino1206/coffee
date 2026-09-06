<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::patch('orders/{order:number}', [OrderController::class, 'update'])->name('orders.update');

        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::patch('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::get('leads/{lead}/file', [LeadController::class, 'attachment'])->name('leads.attachment');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.update');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
