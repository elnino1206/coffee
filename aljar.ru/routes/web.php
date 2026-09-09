<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\Shop\WholesaleLeadController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/* Ролик первого экрана отдаётся приложением, а не как обычный файл.

   Перемотка возможна только там, где сервер умеет отдавать куски
   (`Range`). Встроенный сервер PHP, на котором работает `artisan serve`,
   заголовок игнорирует и всегда возвращает файл целиком — браузер
   считает такое видео неперематываемым и молча отбрасывает каждое
   присвоение `currentTime`. Сцена со скрабом при этом стоит на первом
   кадре, будто ничего не происходит.

   `BinaryFileResponse` куски отдавать умеет. На боевом сервере отдачей
   занимается nginx, и сюда запрос не доходит вовсе — файл лежит на
   месте, в `public/video`. */
Route::get('media/{film}', function (string $film) {
    abort_unless(preg_match('/^[a-z0-9-]+\.mp4$/', $film) === 1, 404);

    $path = public_path("video/{$film}");

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->name('media.film');

// Справочные страницы: только вёрстка, данных с сервера не требуют.
Route::inertia('delivery', 'info/Delivery')->name('info.delivery');
Route::inertia('about', 'info/About')->name('info.about');
Route::get('wholesale', [WholesaleLeadController::class, 'show'])->name('info.wholesale');
Route::post('wholesale', [WholesaleLeadController::class, 'store'])->name('info.wholesale.store');
Route::inertia('contacts', 'info/Contacts')->name('info.contacts');
Route::inertia('legal', 'info/Legal')->name('info.legal');

// Подписка и журнал берут данные с сервера: сорта с ценами и список статей.
Route::get('subscription', [SubscriptionController::class, 'show'])->name('info.subscription');
Route::get('blog', [BlogController::class, 'index'])->name('info.blog');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('info.article');

// Подписка на рассылку — с любой страницы, где стоит форма.
Route::post('newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('account', [AccountController::class, 'show'])->name('account');
    Route::post('account/orders/{order:number}/repeat', [AccountController::class, 'repeat'])->name('account.orders.repeat');

    // Подписка принадлежит покупателю, поэтому и оформление, и
    // управление живут за входом.
    Route::post('subscription', [SubscriptionController::class, 'store'])->name('info.subscription.store');
    Route::post('account/subscriptions/{subscription}/pause', [AccountController::class, 'pauseSubscription'])->name('account.subscriptions.pause');
    Route::post('account/subscriptions/{subscription}/resume', [AccountController::class, 'resumeSubscription'])->name('account.subscriptions.resume');
    Route::post('account/subscriptions/{subscription}/cancel', [AccountController::class, 'cancelSubscription'])->name('account.subscriptions.cancel');

    // Стартовый набор уводил после входа на /dashboard. Кабинет у нас
    // один и живёт по /account — прежний адрес остаётся ссылкой на него,
    // чтобы старые закладки и ссылки набора не упирались в 404.
    Route::redirect('dashboard', '/account')->name('dashboard');
});

require __DIR__.'/catalog.php';
require __DIR__.'/shop.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
