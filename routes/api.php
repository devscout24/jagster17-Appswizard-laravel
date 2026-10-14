<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessOnboardingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContractorBrandingController;
use App\Http\Controllers\Api\ContractorController;
use App\Http\Controllers\Api\ContractorGalleryController;
use App\Http\Controllers\Api\ContractorInvoiceController;
use App\Http\Controllers\Api\ContractorPortalController;
use App\Http\Controllers\Api\ContractorProductController;
use App\Http\Controllers\Api\ContractorServiceController;
use App\Http\Controllers\Api\ContractorSubscriptionController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\CustomerReviewController;
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

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // refresh accepts an expired token that is still inside the refresh window, so it is not behind auth:api
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Settings screens (customer & contractor)
Route::prefix('account')->middleware('auth:api')->group(function () {
    Route::get('/settings', [AccountController::class, 'show']);
    Route::put('/settings', [AccountController::class, 'update']);
    Route::put('/password', [AccountController::class, 'changePassword']);
    Route::post('/avatar', [AccountController::class, 'uploadAvatar']);
});

Route::prefix('business/onboarding')->group(function () {
    Route::post('/account', [BusinessOnboardingController::class, 'account'])->middleware('throttle:auth');
    Route::post('/step-1', [BusinessOnboardingController::class, 'account'])->middleware('throttle:auth');

    Route::middleware(['auth:api', 'role:business,api'])->group(function () {
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
    Route::post('/select', [MembershipPlanController::class, 'select'])->middleware(['auth:api', 'role:business,api']);
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

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [QuoteRequestController::class, 'store'])->middleware('role:customer,api');
        Route::get('/', [QuoteRequestController::class, 'index']);
        Route::get('/{quote}', [QuoteRequestController::class, 'show'])->whereNumber('quote');
    });
});

Route::prefix('customer')->middleware(['auth:api', 'role:customer,api'])->group(function () {
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

    // Reviews (Node 2872-1242)
    Route::get('/reviews', [CustomerReviewController::class, 'index']);
    Route::post('/reviews', [CustomerReviewController::class, 'store']);
});

Route::prefix('contractor')->middleware(['auth:api', 'role:business,api'])->group(function () {
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
    
    // Invoices (Node 3422-1971)
    Route::get('/invoices', [ContractorPortalController::class, 'invoices']);
    Route::post('/invoices', [ContractorPortalController::class, 'createInvoice']);
    Route::get('/invoices/{id}', [ContractorPortalController::class, 'invoiceDetails'])->whereNumber('id');
    Route::put('/invoices/{id}', [ContractorInvoiceController::class, 'update'])->whereNumber('id');
    Route::delete('/invoices/{id}', [ContractorInvoiceController::class, 'destroy'])->whereNumber('id');
    Route::post('/invoices/{id}/approve', [ContractorInvoiceController::class, 'approve'])->whereNumber('id');
    Route::get('/invoices/{id}/send', [ContractorInvoiceController::class, 'sendPreview'])->whereNumber('id');
    Route::post('/invoices/{id}/send', [ContractorInvoiceController::class, 'send'])->whereNumber('id');
    Route::post('/invoices/{id}/mark-paid', [ContractorInvoiceController::class, 'markPaid'])->whereNumber('id');
    Route::post('/invoices/{id}/duplicate', [ContractorInvoiceController::class, 'duplicate'])->whereNumber('id');
    Route::get('/invoices/{id}/pdf', [ContractorInvoiceController::class, 'download'])->whereNumber('id');

    // Invoice customers (Add Customer modal)
    Route::get('/customers', [ContractorInvoiceController::class, 'customers']);
    Route::post('/customers', [ContractorInvoiceController::class, 'addCustomer']);

    // My Services (Node 3123-6885)
    Route::get('/services', [ContractorServiceController::class, 'index']);
    Route::post('/services', [ContractorServiceController::class, 'store']);
    Route::get('/services/{id}', [ContractorServiceController::class, 'show'])->whereNumber('id');
    Route::put('/services/{id}', [ContractorServiceController::class, 'update'])->whereNumber('id');
    Route::post('/services/{id}/toggle-status', [ContractorServiceController::class, 'toggleStatus'])->whereNumber('id');
    Route::delete('/services/{id}', [ContractorServiceController::class, 'destroy'])->whereNumber('id');

    // Products (Node 3299-1047)
    Route::get('/products', [ContractorProductController::class, 'index']);
    Route::post('/products', [ContractorProductController::class, 'store']);
    Route::get('/products/{id}', [ContractorProductController::class, 'show'])->whereNumber('id');
    Route::put('/products/{id}', [ContractorProductController::class, 'update'])->whereNumber('id');
    Route::post('/products/{id}/toggle-status', [ContractorProductController::class, 'toggleStatus'])->whereNumber('id');
    Route::delete('/products/{id}', [ContractorProductController::class, 'destroy'])->whereNumber('id');

    // Gallery (Node 3299-2072)
    Route::get('/gallery', [ContractorGalleryController::class, 'index']);
    Route::post('/gallery', [ContractorGalleryController::class, 'store']);
    Route::put('/gallery/{id}', [ContractorGalleryController::class, 'update'])->whereNumber('id');
    Route::delete('/gallery/{id}', [ContractorGalleryController::class, 'destroy'])->whereNumber('id');

    // Banner (Node 3300-1049) & Website (Node 3300-1275)
    Route::get('/banner', [ContractorBrandingController::class, 'banner']);
    Route::post('/banner', [ContractorBrandingController::class, 'updateBanner']);
    Route::delete('/banner', [ContractorBrandingController::class, 'deleteBanner']);
    Route::get('/website', [ContractorBrandingController::class, 'website']);
    Route::put('/website', [ContractorBrandingController::class, 'updateWebsite']);

    // Subscription (Node 3241-1786)
    Route::get('/subscription', [ContractorSubscriptionController::class, 'show']);
    Route::post('/subscription/change', [ContractorSubscriptionController::class, 'change']);
    Route::post('/subscription/cancel', [ContractorSubscriptionController::class, 'cancel']);
    Route::get('/subscription/billing/{id}/receipt', [ContractorSubscriptionController::class, 'receipt'])->whereNumber('id');
    
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
    Route::post('/profile/media', [ContractorBrandingController::class, 'uploadProfileMedia']);
    Route::delete('/profile/media/{field}', [ContractorBrandingController::class, 'deleteProfileMedia']);

    Route::get('/analytics', [ContractorPortalController::class, 'analytics']);
});

Route::prefix('messages')->middleware('auth:api')->group(function () {
    Route::get('/', [MessageController::class, 'index']);
    Route::post('/start', [MessageController::class, 'start']);
    Route::get('/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/{id}', [MessageController::class, 'show'])->whereNumber('id');
    Route::post('/{id}', [MessageController::class, 'sendMessage'])->whereNumber('id');
});

Route::prefix('notifications')->middleware('auth:api')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::delete('/', [NotificationController::class, 'clearAll']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
});
