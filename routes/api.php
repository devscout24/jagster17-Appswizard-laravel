<?php

use App\Http\Controllers\Api\AccountSettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessOnboardingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Contractor\BannerController as ContractorBannerController;
use App\Http\Controllers\Api\Contractor\BusinessDocumentController;
use App\Http\Controllers\Api\Contractor\CustomerController as ContractorCustomerController;
use App\Http\Controllers\Api\Contractor\GalleryController;
use App\Http\Controllers\Api\Contractor\InvoiceController as ContractorInvoiceController;
use App\Http\Controllers\Api\Contractor\ProductController as ContractorProductController;
use App\Http\Controllers\Api\Contractor\ServiceController as ContractorServiceController;
use App\Http\Controllers\Api\Contractor\SubscriptionController as ContractorSubscriptionController;
use App\Http\Controllers\Api\Contractor\WebsiteController;
use App\Http\Controllers\Api\ContractorController;
use App\Http\Controllers\Api\ContractorPortalController;
use App\Http\Controllers\Api\Customer\ReviewController as CustomerReviewController;
use App\Http\Controllers\Api\CustomerPortalController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\GivingBackController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\QuoteRequestController;
use App\Http\Controllers\Api\SiteContentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  (all prefixed with /api)
|--------------------------------------------------------------------------
| Full reference: docs/API.md
*/

/*
| Authentication
*/
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/password', [AuthController::class, 'changePassword']);
    });
});

/*
| Account settings (customers and contractors)
*/
Route::middleware('auth:api')->prefix('account')->group(function () {
    Route::get('/settings', [AccountSettingsController::class, 'show']);
    Route::put('/settings', [AccountSettingsController::class, 'update']);
    Route::put('/notification-preferences', [AccountSettingsController::class, 'updateNotifications']);
});

/*
| Contractor signup ("Join as a Pro")
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

/*
| Public website
*/
Route::get('/site-settings', [SiteContentController::class, 'settings']);
Route::get('/pages/{slug}', [SiteContentController::class, 'page'])->where('slug', '[a-z0-9-]+');
Route::get('/banners', [SiteContentController::class, 'banners']);

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
    Route::post('/nominate', [GivingBackController::class, 'nominate'])->middleware('throttle:10,1');
});

Route::get('/faqs', [FaqController::class, 'index']);

Route::prefix('contact')->group(function () {
    Route::get('/', [ContactController::class, 'info']);
    Route::post('/', [ContactController::class, 'send'])->middleware('throttle:10,1');
});

// Request a Quote: guests may submit (with name + email); listing requires login
Route::prefix('quotes')->group(function () {
    Route::get('/wizard-config', [QuoteRequestController::class, 'config']);
    Route::post('/', [QuoteRequestController::class, 'store'])->middleware('throttle:10,1');

    Route::middleware('auth:api')->group(function () {
        Route::get('/', [QuoteRequestController::class, 'index']);
        Route::get('/{quote}', [QuoteRequestController::class, 'show'])->whereNumber('quote');
    });
});

/*
| Customer dashboard
*/
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

    Route::get('/reviews', [CustomerReviewController::class, 'index']);
    Route::post('/reviews', [CustomerReviewController::class, 'store']);
    Route::put('/reviews/{id}', [CustomerReviewController::class, 'update'])->whereNumber('id');
});

/*
| Contractor dashboard
*/
Route::prefix('contractor')->middleware(['auth:api', 'role:business,api'])->group(function () {
    Route::get('/dashboard', [ContractorPortalController::class, 'dashboard']);
    Route::get('/analytics', [ContractorPortalController::class, 'analytics']);
    Route::post('/toggle-availability', [ContractorPortalController::class, 'toggleAvailability']);

    // Quote requests (leads)
    Route::get('/leads', [ContractorPortalController::class, 'leads']);
    Route::get('/leads/{id}', [ContractorPortalController::class, 'leadDetails'])->whereNumber('id');
    Route::post('/leads/{id}/send-quote', [ContractorPortalController::class, 'sendQuote'])->whereNumber('id');
    Route::post('/leads/{id}/decline', [ContractorPortalController::class, 'declineLead'])->whereNumber('id');

    // Projects
    Route::get('/projects', [ContractorPortalController::class, 'projects']);
    Route::post('/projects', [ContractorPortalController::class, 'storeProject']);
    Route::get('/projects/{id}', [ContractorPortalController::class, 'projectDetails'])->whereNumber('id');
    Route::put('/projects/{id}/progress', [ContractorPortalController::class, 'updateProjectProgress'])->whereNumber('id');

    // Invoices
    Route::get('/invoices', [ContractorPortalController::class, 'invoices']);
    Route::get('/invoices/{id}', [ContractorPortalController::class, 'invoiceDetails'])->whereNumber('id');
    Route::post('/invoices', [ContractorInvoiceController::class, 'store']);
    Route::put('/invoices/{id}', [ContractorInvoiceController::class, 'update'])->whereNumber('id');
    Route::delete('/invoices/{id}', [ContractorInvoiceController::class, 'destroy'])->whereNumber('id');
    Route::post('/invoices/{id}/send', [ContractorInvoiceController::class, 'send'])->whereNumber('id');
    Route::post('/invoices/{id}/mark-paid', [ContractorInvoiceController::class, 'markPaid'])->whereNumber('id');
    Route::post('/invoices/{id}/duplicate', [ContractorInvoiceController::class, 'duplicate'])->whereNumber('id');

    // Customers (invoice "Add a customer")
    Route::get('/customers', [ContractorCustomerController::class, 'index']);
    Route::post('/customers', [ContractorCustomerController::class, 'store']);
    Route::put('/customers/{id}', [ContractorCustomerController::class, 'update'])->whereNumber('id');
    Route::delete('/customers/{id}', [ContractorCustomerController::class, 'destroy'])->whereNumber('id');

    // Messages
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/{id}', [MessageController::class, 'show'])->whereNumber('id');
    Route::post('/messages/{id}', [MessageController::class, 'sendMessage'])->whereNumber('id');

    // Services & products
    Route::get('/services', [ContractorServiceController::class, 'index']);
    Route::post('/services', [ContractorServiceController::class, 'store']);
    Route::put('/services/{id}', [ContractorServiceController::class, 'update'])->whereNumber('id');
    Route::post('/services/{id}/toggle', [ContractorServiceController::class, 'toggle'])->whereNumber('id');
    Route::delete('/services/{id}', [ContractorServiceController::class, 'destroy'])->whereNumber('id');

    Route::get('/products', [ContractorProductController::class, 'index']);
    Route::post('/products', [ContractorProductController::class, 'store']);
    Route::put('/products/{id}', [ContractorProductController::class, 'update'])->whereNumber('id');
    Route::post('/products/{id}/toggle', [ContractorProductController::class, 'toggle'])->whereNumber('id');
    Route::delete('/products/{id}', [ContractorProductController::class, 'destroy'])->whereNumber('id');

    // Gallery, banner, website
    Route::get('/gallery', [GalleryController::class, 'index']);
    Route::post('/gallery', [GalleryController::class, 'store']);
    Route::put('/gallery/{id}', [GalleryController::class, 'update'])->whereNumber('id');
    Route::delete('/gallery/{id}', [GalleryController::class, 'destroy'])->whereNumber('id');

    Route::get('/banner', [ContractorBannerController::class, 'show']);
    Route::post('/banner', [ContractorBannerController::class, 'store']);

    Route::get('/website', [WebsiteController::class, 'show']);
    Route::put('/website', [WebsiteController::class, 'update']);

    // Business profile & documents
    Route::get('/profile', [ContractorPortalController::class, 'getProfile']);
    Route::put('/profile', [ContractorPortalController::class, 'updateProfile']);
    Route::get('/documents', [BusinessDocumentController::class, 'index']);
    Route::post('/documents', [BusinessDocumentController::class, 'store']);
    Route::delete('/documents/{id}', [BusinessDocumentController::class, 'destroy'])->whereNumber('id');

    // Reviews
    Route::get('/reviews', [ContractorPortalController::class, 'reviews']);
    Route::post('/reviews/{id}/reply', [ContractorPortalController::class, 'replyReview'])->whereNumber('id');
    Route::post('/reviews/{id}/toggle-feature', [ContractorPortalController::class, 'toggleFeatureReview'])->whereNumber('id');

    // Subscription & billing
    Route::get('/subscription', [ContractorSubscriptionController::class, 'show']);
    Route::post('/subscription/change', [ContractorSubscriptionController::class, 'change']);
    Route::get('/subscription/receipts/{id}', [ContractorSubscriptionController::class, 'receipt'])->whereNumber('id');
});

/*
| Messaging & notifications (any logged-in user)
*/
Route::prefix('messages')->middleware('auth:api')->group(function () {
    Route::get('/', [MessageController::class, 'index']);
    Route::post('/start', [MessageController::class, 'start']);
    Route::get('/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/{id}', [MessageController::class, 'show'])->whereNumber('id');
    Route::post('/{id}', [MessageController::class, 'sendMessage'])->whereNumber('id');
});

Route::prefix('notifications')->middleware('auth:api')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->whereNumber('id');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
});
