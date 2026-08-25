<?php

use App\Http\Controllers\CoffeeCatalogController;
use Illuminate\Support\Facades\Route;

// Разделы каталога симметричны: у кофе и оборудования разные фильтры и
// разные карточки, но одинаковый адрес.
Route::redirect('catalog', '/catalog/coffee')->name('catalog');

Route::get('catalog/coffee', [CoffeeCatalogController::class, 'index'])->name('catalog.coffee.index');
Route::get('catalog/coffee/{coffee:slug}', [CoffeeCatalogController::class, 'show'])->name('catalog.coffee.show');
