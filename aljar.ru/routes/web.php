<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Справочные страницы: только вёрстка, данных с сервера не требуют.
Route::inertia('delivery', 'info/Delivery')->name('info.delivery');
Route::inertia('about', 'info/About')->name('info.about');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/catalog.php';
require __DIR__.'/shop.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
