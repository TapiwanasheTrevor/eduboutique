<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ShopController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\VideosController;

// Frontend Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', function () {
    return Inertia::render('about/AboutPage');
})->name('about');

Route::get('/shop', [ShopController::class, 'index'])->name('shop');

Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.detail');

Route::get('/videos', [VideosController::class, 'index'])->name('videos');

Route::get('/contact', function () {
    return Inertia::render('contact/ContactPage');
})->name('contact');

Route::get('/cart', function () {
    return Inertia::render('cart/CartPage');
})->name('cart');

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
