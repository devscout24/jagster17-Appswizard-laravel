<?php

use App\Http\Controllers\Api\BusinessOnboardingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContractorController;
use App\Http\Controllers\Api\ContractorPortalController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\GivingBackController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\QuoteRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('business/onboarding')->group(function () {
    Route::post('/account', [BusinessOnboardingController::class, 'account']);
    Route::post('/step-1', [BusinessOnboardingController::class, 'account']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/status', [BusinessOnboardingController::class, 'status']);
        Route::post('/plan', [BusinessOnboardingController::class, 'selectPlan']);
        Route::post('/checkout', [BusinessOnboardingController::class, 'checkout']);
        Route::post('/id-me', [BusinessOnboardingController::class, 'verifyIdMe']);
        Route::post('/business-info', [BusinessOnboardingController::class, 'saveBusinessInfo']);
        Route::post('/profile', [BusinessOnboardingController::class, 'saveProfile']);
        Route::post('/social-links', [BusinessOnboardingController::class, 'saveSocialLinks']);
        Route::post('/services', [BusinessOnboardingController::class, 'saveServices']);
        Route::get('/review', [BusinessOnboardingController::class, 'review']);
        Route::post('/submit', [BusinessOnboardingController::class, 'submit']);
    });
});

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

Route::prefix('membership-plans')->group(function () {
    Route::get('/', [MembershipPlanController::class, 'index']);
    Route::get('/{plan}', [MembershipPlanController::class, 'show'])->whereNumber('plan');
    Route::post('/select', [MembershipPlanController::class, 'select'])->middleware('auth:api');
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

Route::prefix('contractor')->group(function () {
    Route::get('/dashboard', [ContractorPortalController::class, 'dashboard']);
    Route::post('/toggle-availability', [ContractorPortalController::class, 'toggleAvailability']);
    
    // Leads & Quotes
    Route::get('/leads', [ContractorPortalController::class, 'leads']);
    Route::get('/leads/{id}', [ContractorPortalController::class, 'leadDetails'])->whereNumber('id');
    Route::post('/leads/{id}/send-quote', [ContractorPortalController::class, 'sendQuote'])->whereNumber('id');
    Route::post('/leads/{id}/decline', [ContractorPortalController::class, 'declineLead'])->whereNumber('id');
    
    // Projects (Node 3223-1398)
    Route::get('/projects', [ContractorPortalController::class, 'projects']);
    Route::post('/projects', [ContractorPortalController::class, 'storeProject']);
    Route::get('/projects/{id}', [ContractorPortalController::class, 'projectDetails'])->whereNumber('id');
    Route::put('/projects/{id}/progress', [ContractorPortalController::class, 'updateProjectProgress'])->whereNumber('id');
    
    // Invoices (Node 3354-11)
    Route::get('/invoices', [ContractorPortalController::class, 'invoices']);
    Route::post('/invoices', [ContractorPortalController::class, 'createInvoice']);
    Route::get('/invoices/{id}', [ContractorPortalController::class, 'invoiceDetails'])->whereNumber('id');
    
    // Messages (Node 3223-2212)
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/{id}', [MessageController::class, 'show'])->whereNumber('id');
    Route::post('/messages/{id}', [MessageController::class, 'sendMessage'])->whereNumber('id');
    
    // Reviews & Testimonials (Node 3228-4501 & 3370-11)
    Route::get('/reviews', [ContractorPortalController::class, 'reviews']);
    Route::post('/reviews/{id}/reply', [ContractorPortalController::class, 'replyReview'])->whereNumber('id');
    Route::post('/reviews/{id}/toggle-feature', [ContractorPortalController::class, 'toggleFeatureReview'])->whereNumber('id');

    // Profile Settings (Node 3363-11)
    Route::get('/profile', [ContractorPortalController::class, 'getProfile']);
    Route::put('/profile', [ContractorPortalController::class, 'updateProfile']);

    Route::get('/analytics', [ContractorPortalController::class, 'analytics']);
});

Route::prefix('messages')->group(function () {
    Route::get('/', [MessageController::class, 'index']);
    Route::post('/start', [MessageController::class, 'start']);
    Route::get('/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/{id}', [MessageController::class, 'show'])->whereNumber('id');
    Route::post('/{id}', [MessageController::class, 'sendMessage'])->whereNumber('id');
});

Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
});










