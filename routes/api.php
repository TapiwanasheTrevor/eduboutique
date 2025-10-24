<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 Routes
Route::prefix('v1')->group(function () {

    // Products Endpoints
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/products/featured', [ProductController::class, 'featured'])->name('api.products.featured');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('api.products.show');

    // Categories Endpoints
    Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('api.categories.show');

    // Inquiries Endpoints
    Route::post('/inquiries', [InquiryController::class, 'store'])->name('api.inquiries.store');

    // Contact Form Endpoints
    Route::post('/contact', [ContactController::class, 'store'])->name('api.contact.store');

    // Videos Endpoints
    Route::get('/videos', [VideoController::class, 'index'])->name('api.videos.index');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('api.videos.show');

    // Newsletter Endpoints
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('api.newsletter.subscribe');

    // Settings Endpoints
    Route::get('/settings', [SettingController::class, 'index'])->name('api.settings.index');

});
