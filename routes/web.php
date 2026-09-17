<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ContractorController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\QuoteRequestController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\VeteranNominationController;
use Illuminate\Support\Facades\Route;

// Root route
Route::get('/', function () {
    return view('welcome');
});

// Admin root redirect
Route::get('/admin', function () {
    return redirect()->route('admin.dashboard');
});

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Protected Admin Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['web', 'admin.auth'])->group(function () {
    // Dashboard Overview
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Contractors & Businesses
    Route::resource('contractors', ContractorController::class);
    Route::post('contractors/{id}/toggle-badge', [ContractorController::class, 'toggleBadge'])->name('contractors.toggle-badge');

    // Customers
    Route::resource('customers', CustomerController::class);

    // Categories Taxonomy
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Contractor Services
    Route::resource('services', ServiceController::class);

    // Marketplace Products
    Route::resource('products', ProductController::class);

    // Quotes & Leads
    Route::resource('quotes', QuoteRequestController::class);

    // Projects Management
    Route::resource('projects', ProjectController::class);

    // Invoices & Payments
    Route::resource('invoices', InvoiceController::class);
    Route::get('payments', [InvoiceController::class, 'payments'])->name('payments.index');

    // Subscriptions & Plans
    Route::resource('plans', SubscriptionPlanController::class)->except(['show']);
    Route::get('subscriptions', [SubscriptionPlanController::class, 'subscriptions'])->name('subscriptions.index');
    Route::post('subscriptions/{id}/cancel', [SubscriptionPlanController::class, 'cancelSubscription'])->name('subscriptions.cancel');
    Route::get('billing-history', [SubscriptionPlanController::class, 'billingHistory'])->name('billing-history.index');

    // Reviews & Testimonials
    Route::resource('reviews', ReviewController::class)->only(['index', 'show', 'destroy']);
    Route::post('reviews/{id}/toggle-featured', [ReviewController::class, 'toggleFeatured'])->name('reviews.toggle-featured');

    // Veteran Nominations (Giving Back)
    Route::resource('veteran-nominations', VeteranNominationController::class)->only(['index', 'show', 'destroy']);
    Route::put('veteran-nominations/{id}/status', [VeteranNominationController::class, 'updateStatus'])->name('veteran-nominations.update-status');

    // Support Inquiries
    Route::resource('contact-messages', ContactMessageController::class)->only(['index', 'show', 'destroy']);
    Route::put('contact-messages/{id}/status', [ContactMessageController::class, 'updateStatus'])->name('contact-messages.update-status');

    // FAQs
    Route::resource('faqs', FaqController::class)->except(['show']);

    // Admin Users Management (Super Admin only access via policy/role if required)
    Route::resource('users', AdminUserController::class)->except(['show']);

    // Profile Settings
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
});
