<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContractorController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\GivingBackController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\QuoteRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('home')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/search', [HomeController::class, 'search']);
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show'])->whereNumber('category');
});

Route::prefix('contractors')->group(function () {
    Route::get('/', [ContractorController::class, 'index']);
    Route::get('/{contractor}', [ContractorController::class, 'show'])->whereNumber('contractor');
});

Route::prefix('marketplace')->group(function () {
    Route::get('/', [MarketplaceController::class, 'landing']);
    Route::get('/products', [MarketplaceController::class, 'index']);
    Route::get('/products/{product}', [MarketplaceController::class, 'show'])->whereNumber('product');
});

Route::prefix('giving-back')->group(function () {
    Route::get('/', [GivingBackController::class, 'index']);
    Route::post('/nominate', [GivingBackController::class, 'nominate']);
});

Route::get('/faqs', [FaqController::class, 'index']);

Route::prefix('contact')->group(function () {
    Route::get('/', [ContactController::class, 'info']);
    Route::post('/', [ContactController::class, 'send']);
});

Route::prefix('quotes')->group(function () {
    Route::get('/wizard-config', [QuoteRequestController::class, 'config']);
    Route::post('/', [QuoteRequestController::class, 'store']);
    Route::get('/', [QuoteRequestController::class, 'index']);
    Route::get('/{quote}', [QuoteRequestController::class, 'show'])->whereNumber('quote');
});

Route::prefix('customer')->group(function () {
    Route::get('/dashboard', [CustomerPortalController::class, 'dashboard']);
    Route::get('/requests', [CustomerPortalController::class, 'requests']);
    Route::get('/quotes', [CustomerPortalController::class, 'quotes']);
    Route::get('/quotes/{id}', [CustomerPortalController::class, 'quoteDetails'])->whereNumber('id');
    Route::post('/quotes/{id}/accept', [CustomerPortalController::class, 'acceptQuote'])->whereNumber('id');
    Route::post('/quotes/{id}/decline', [CustomerPortalController::class, 'declineQuote'])->whereNumber('id');
    Route::get('/invoices', [CustomerPortalController::class, 'invoices']);
    Route::get('/invoices/{id}', [CustomerPortalController::class, 'invoiceDetails'])->whereNumber('id');
    Route::post('/invoices/{id}/pay', [CustomerPortalController::class, 'payInvoice'])->whereNumber('id');
    Route::get('/payments', [CustomerPortalController::class, 'payments']);
    Route::get('/saved-contractors', [CustomerPortalController::class, 'savedContractors']);
    Route::post('/saved-contractors/{businessId}/toggle', [CustomerPortalController::class, 'toggleSavedContractor'])->whereNumber('businessId');
    Route::delete('/saved-contractors/{businessId}', [CustomerPortalController::class, 'removeSavedContractor'])->whereNumber('businessId');
});








