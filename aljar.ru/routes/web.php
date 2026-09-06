<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Справочные страницы: только вёрстка, данных с сервера не требуют.
Route::inertia('delivery', 'info/Delivery')->name('info.delivery');
Route::inertia('about', 'info/About')->name('info.about');
Route::inertia('wholesale', 'info/Wholesale')->name('info.wholesale');
Route::inertia('contacts', 'info/Contacts')->name('info.contacts');
Route::inertia('legal', 'info/Legal')->name('info.legal');

// Подписка и журнал берут данные с сервера: сорта с ценами и список статей.
Route::get('subscription', [SubscriptionController::class, 'show'])->name('info.subscription');
Route::get('blog', [BlogController::class, 'index'])->name('info.blog');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('info.article');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('account', [AccountController::class, 'show'])->name('account');
    Route::post('account/orders/{order:number}/repeat', [AccountController::class, 'repeat'])->name('account.orders.repeat');

    // Стартовый набор уводил после входа на /dashboard. Кабинет у нас
    // один и живёт по /account — прежний адрес остаётся ссылкой на него,
    // чтобы старые закладки и ссылки набора не упирались в 404.
    Route::redirect('dashboard', '/account')->name('dashboard');
});

require __DIR__.'/catalog.php';
require __DIR__.'/shop.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
