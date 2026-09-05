<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Справочные страницы: только вёрстка, данных с сервера не требуют.
Route::inertia('delivery', 'info/Delivery')->name('info.delivery');
Route::inertia('about', 'info/About')->name('info.about');
Route::inertia('wholesale', 'info/Wholesale')->name('info.wholesale');

// Подписка и журнал берут данные с сервера: сорта с ценами и список статей.
Route::get('subscription', [SubscriptionController::class, 'show'])->name('info.subscription');
Route::get('blog', [BlogController::class, 'index'])->name('info.blog');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('info.article');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/catalog.php';
require __DIR__.'/shop.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
