# ValorHub API Reference

Base URL: `{APP_URL}/api` (local: `http://localhost:8000/api`)

## Conventions

**Response envelope** (every endpoint):

```json
{ "status": true, "message": "Invoice sent to client successfully.", "data": { }, "code": 200 }
```

**Validation errors** return `422` with Laravel's standard shape:

```json
{ "message": "The email field is required.", "errors": { "email": ["The email field is required."] } }
```

**Authentication**: JWT. Log in or register, then send `Authorization: Bearer <access_token>`.
Tokens expire after `JWT_TTL` minutes (default 60); call `POST /auth/refresh` to get a new one.

| Status | Meaning |
|---|---|
| `401` | Not logged in, or the token expired |
| `403` | Logged in, but the wrong role (e.g. a customer calling a contractor endpoint) |
| `404` | Not found, or it belongs to someone else |
| `422` | Validation failed |
| `429` | Too many attempts (login, signup, contact and quote forms are rate-limited to 10 per minute) |

**Roles**: `customer` (homeowners), `business` (contractors). Admins use the web admin panel at `/admin`, not the API.

**File uploads** use `multipart/form-data`. Uploaded files are served from `{APP_URL}/storage/...`.

## Request bodies for key endpoints

### `POST /auth/register` (customer)
`name`, `email`, `password`, `password_confirmation`, `terms_accepted` (true); optional `phone`, `city`, `state`.
Returns `data.user` and `data.token.access_token`.

### `POST /auth/login`
`email`, `password`. Works for customers and contractors; `data.user.role` tells the frontend which dashboard to open.

### `POST /auth/forgot-password` / `POST /auth/reset-password`
Forgot: `email`. The email links to `FRONTEND_URL/reset-password?token=...&email=...`.
Reset: `token`, `email`, `password`, `password_confirmation`.

### `POST /auth/password` (change password)
`current_password`, `password`, `password_confirmation`.

### `PUT /account/settings`, `PUT /account/notification-preferences`
Settings: `name`, `email`, `phone`. Notifications: booleans `quotes`, `messages`, `payments`, `reviews`.

### `POST /quotes` (Request a Quote)
`description`, `timeline` (`asap|within_1_week|within_1_month|flexible`), `zip_code`, `city`, `state`;
optional `service_type`, `category_id`, `contractor_id`, `budget_range` (`under_1000|1000_5000|5000_15000|15000_plus`), `street_address`.
**Guests also send** `name`, `email` (optional `phone`).

### `POST /customer/reviews`
`project_id` (a completed project of yours), `rating` (1–5), `comment` (10–1000 chars). `PUT /customer/reviews/{id}` accepts `rating` and/or `comment`.

### `POST /contractor/invoices` and `PUT /contractor/invoices/{id}` (New Invoice screen)
- Customer: `contractor_customer_id` (from `/contractor/customers`) **or** `customer_id` (a ValorHub user)
- `due_at` (date), optional `issued_at`, `payment_terms` (e.g. `Net 15`), `po_number`, `invoice_number`
- `items[]`: `description`, `quantity`, `unit_price`, optional `details`
- optional `discount_percent` (0–100), `notes`, `footer`, `tags[]`, `attachments[]`, `require_terms_acceptance`
- `status`: `draft` (Save as draft) or `pending` (Save and send)

Totals are calculated on the server: `subtotal`, `discount_amount`, `amount` (total due).

### Invoice actions
- `POST /contractor/invoices/{id}/send`: `to` (email), `subject`, optional `message`
- `POST /contractor/invoices/{id}/mark-paid`: `paid_on` (date), `payment_method` (`bank_transfer|credit_card|check|cash|other`)
- `POST /contractor/invoices/{id}/duplicate`: no body; returns the new draft
- `DELETE /contractor/invoices/{id}`: drafts and unpaid invoices only

### `POST /contractor/customers` (Add a customer)
`name`, `email`, optional `phone`, `billing_address`. `GET /contractor/customers?search=` returns `recent` (3) and `customers`.

### Products and services (My Services / Products)
- Products: `name`, `category_id` (a `product` category), `price`, optional `unit`, `description`, `image` (file), `external_url`, `in_stock`
- Services: `name`, `category_id` (a `service` category), `pricing_type` (`fixed|hourly|sqft|custom_quote`), `price` (not needed for `custom_quote`), optional `unit`, `description`
- `POST .../{id}/toggle` switches Active / Off. Plan limits are enforced (Free: 3 services, Pro: 10, Elite: unlimited).

### Gallery, banner, website, documents
- `POST /contractor/gallery`: `photos[]` (images, up to 10MB each), optional `category` (`before_after|project_photos|team|equipment`). Plan gallery limits apply.
- `PUT /contractor/gallery/{id}`: `caption`, `category`, `sort_order`
- `POST /contractor/banner` (Elite only): `image` (JPG/PNG, max 5MB), optional `headline`. Goes to the admin for approval.
- `PUT /contractor/website`: `custom_domain` (full URLs are cleaned up, e.g. `https://www.site.com/` → `www.site.com`)
- `POST /contractor/documents`: `type` (`license_insurance|logo|cover_photo|other`), `file`. License documents are reviewed by an admin.

### `PUT /contractor/profile` (Business Profile)
Any of: `business_name`, `business_category` (`Blue Collar|White Collar|Grey Collar|Gold Collar|Other`), `street_address`, `city`, `state`, `zip_code`, `bio`,
`years_experience`, `license_number`, `insurance_company`, `insurance_policy_number`, `offers_emergency_service`, `is_available_today`, `languages[]`,
`tagline`, `invoice_title`, `business_email`, social URLs (`website_url`, `facebook_url`, `instagram_url`, `linkedin_url`, `twitter_url`, `youtube_url`, `tiktok_url`).

### `POST /contractor/subscription/change`
`plan_id`, `billing_cycle` (`monthly|annual`). `GET /contractor/subscription/receipts/{id}` downloads a receipt.

## All endpoints

### Authentication

Customers register here. Contractors register through Contractor signup below. Send the token as `Authorization: Bearer <access_token>`.

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `POST` | `/api/auth/forgot-password` | Public | `AuthController@forgotPassword` |
| `POST` | `/api/auth/login` | Public | `AuthController@login` |
| `POST` | `/api/auth/logout` | Logged in | `AuthController@logout` |
| `GET` | `/api/auth/me` | Logged in | `AuthController@me` |
| `POST` | `/api/auth/password` | Logged in | `AuthController@changePassword` |
| `POST` | `/api/auth/refresh` | Logged in | `AuthController@refresh` |
| `POST` | `/api/auth/register` | Public | `AuthController@register` |
| `POST` | `/api/auth/reset-password` | Public | `AuthController@resetPassword` |

### Account settings

Shared by customers and contractors.

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `PUT` | `/api/account/notification-preferences` | Logged in | `AccountSettingsController@updateNotifications` |
| `GET` | `/api/account/settings` | Logged in | `AccountSettingsController@show` |
| `PUT` | `/api/account/settings` | Logged in | `AccountSettingsController@update` |

### Contractor signup (Join as a Pro)

`/account` returns a token; the remaining steps need it.

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `POST` | `/api/business/onboarding/account` | Public | `BusinessOnboardingController@account` |
| `POST` | `/api/business/onboarding/business-info` | Logged in | `BusinessOnboardingController@saveBusinessInfo` |
| `POST` | `/api/business/onboarding/checkout` | Logged in | `BusinessOnboardingController@checkout` |
| `POST` | `/api/business/onboarding/id-me` | Logged in | `BusinessOnboardingController@verifyIdMe` |
| `POST` | `/api/business/onboarding/plan` | Logged in | `BusinessOnboardingController@selectPlan` |
| `POST` | `/api/business/onboarding/profile` | Logged in | `BusinessOnboardingController@saveProfile` |
| `GET` | `/api/business/onboarding/review` | Logged in | `BusinessOnboardingController@review` |
| `POST` | `/api/business/onboarding/services` | Logged in | `BusinessOnboardingController@saveServices` |
| `POST` | `/api/business/onboarding/social-links` | Logged in | `BusinessOnboardingController@saveSocialLinks` |
| `GET` | `/api/business/onboarding/status` | Logged in | `BusinessOnboardingController@status` |
| `POST` | `/api/business/onboarding/step-1` | Public | `BusinessOnboardingController@account` |
| `POST` | `/api/business/onboarding/submit` | Logged in | `BusinessOnboardingController@submit` |

### Site content

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/site-settings` | Public | `SiteContentController@settings` |
| `GET` | `/api/pages/{slug}` | Public | `SiteContentController@page` |
| `GET` | `/api/banners` | Public | `SiteContentController@banners` |

### Home

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/home` | Public | `HomeController@index` |
| `GET` | `/api/home/search` | Public | `HomeController@search` |

### Categories

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/categories` | Public | `CategoryController@index` |
| `GET` | `/api/categories/{category}` | Public | `CategoryController@show` |

### Contractors (public)

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/contractors` | Public | `ContractorController@index` |
| `GET` | `/api/contractors/{contractor}` | Public | `ContractorController@show` |

### Marketplace

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/marketplace` | Public | `MarketplaceController@landing` |
| `GET` | `/api/marketplace/products` | Public | `MarketplaceController@index` |
| `GET` | `/api/marketplace/products/{product}` | Public | `MarketplaceController@show` |

### Membership plans

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/membership-plans` | Public | `MembershipPlanController@index` |
| `POST` | `/api/membership-plans/select` | Contractor | `MembershipPlanController@select` |
| `GET` | `/api/membership-plans/{plan}` | Public | `MembershipPlanController@show` |

### Giving Back

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/giving-back` | Public | `GivingBackController@index` |
| `POST` | `/api/giving-back/nominate` | Public | `GivingBackController@nominate` |

### FAQ

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/faqs` | Public | `FaqController@index` |

### Contact

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/contact` | Public | `ContactController@info` |
| `POST` | `/api/contact` | Public | `ContactController@send` |

### Request a Quote

Guests may submit with `name` + `email`; this creates a passwordless account they can claim by registering with the same email.

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `POST` | `/api/quotes` | Public | `QuoteRequestController@store` |
| `GET` | `/api/quotes` | Logged in | `QuoteRequestController@index` |
| `GET` | `/api/quotes/wizard-config` | Public | `QuoteRequestController@config` |
| `GET` | `/api/quotes/{quote}` | Logged in | `QuoteRequestController@show` |

### Customer dashboard

Requires a customer login.

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/customer/dashboard` | Customer | `CustomerPortalController@dashboard` |
| `GET` | `/api/customer/invoices` | Customer | `CustomerPortalController@invoices` |
| `GET` | `/api/customer/invoices/{id}` | Customer | `CustomerPortalController@invoiceDetails` |
| `POST` | `/api/customer/invoices/{id}/pay` | Customer | `CustomerPortalController@payInvoice` |
| `GET` | `/api/customer/payments` | Customer | `CustomerPortalController@payments` |
| `GET` | `/api/customer/quotes` | Customer | `CustomerPortalController@quotes` |
| `GET` | `/api/customer/quotes/{id}` | Customer | `CustomerPortalController@quoteDetails` |
| `POST` | `/api/customer/quotes/{id}/accept` | Customer | `CustomerPortalController@acceptQuote` |
| `POST` | `/api/customer/quotes/{id}/decline` | Customer | `CustomerPortalController@declineQuote` |
| `GET` | `/api/customer/requests` | Customer | `CustomerPortalController@requests` |
| `GET` | `/api/customer/reviews` | Customer | `ReviewController@index` |
| `POST` | `/api/customer/reviews` | Customer | `ReviewController@store` |
| `PUT` | `/api/customer/reviews/{id}` | Customer | `ReviewController@update` |
| `GET` | `/api/customer/saved-contractors` | Customer | `CustomerPortalController@savedContractors` |
| `DELETE` | `/api/customer/saved-contractors/{businessId}` | Customer | `CustomerPortalController@removeSavedContractor` |
| `POST` | `/api/customer/saved-contractors/{businessId}/toggle` | Customer | `CustomerPortalController@toggleSavedContractor` |

### Contractor dashboard

Requires a contractor (business) login.

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/contractor/analytics` | Contractor | `ContractorPortalController@analytics` |
| `GET` | `/api/contractor/banner` | Contractor | `BannerController@show` |
| `POST` | `/api/contractor/banner` | Contractor | `BannerController@store` |
| `GET` | `/api/contractor/customers` | Contractor | `CustomerController@index` |
| `POST` | `/api/contractor/customers` | Contractor | `CustomerController@store` |
| `PUT` | `/api/contractor/customers/{id}` | Contractor | `CustomerController@update` |
| `DELETE` | `/api/contractor/customers/{id}` | Contractor | `CustomerController@destroy` |
| `GET` | `/api/contractor/dashboard` | Contractor | `ContractorPortalController@dashboard` |
| `GET` | `/api/contractor/documents` | Contractor | `BusinessDocumentController@index` |
| `POST` | `/api/contractor/documents` | Contractor | `BusinessDocumentController@store` |
| `DELETE` | `/api/contractor/documents/{id}` | Contractor | `BusinessDocumentController@destroy` |
| `GET` | `/api/contractor/gallery` | Contractor | `GalleryController@index` |
| `POST` | `/api/contractor/gallery` | Contractor | `GalleryController@store` |
| `PUT` | `/api/contractor/gallery/{id}` | Contractor | `GalleryController@update` |
| `DELETE` | `/api/contractor/gallery/{id}` | Contractor | `GalleryController@destroy` |
| `GET` | `/api/contractor/invoices` | Contractor | `ContractorPortalController@invoices` |
| `POST` | `/api/contractor/invoices` | Contractor | `InvoiceController@store` |
| `GET` | `/api/contractor/invoices/{id}` | Contractor | `ContractorPortalController@invoiceDetails` |
| `PUT` | `/api/contractor/invoices/{id}` | Contractor | `InvoiceController@update` |
| `DELETE` | `/api/contractor/invoices/{id}` | Contractor | `InvoiceController@destroy` |
| `POST` | `/api/contractor/invoices/{id}/duplicate` | Contractor | `InvoiceController@duplicate` |
| `POST` | `/api/contractor/invoices/{id}/mark-paid` | Contractor | `InvoiceController@markPaid` |
| `POST` | `/api/contractor/invoices/{id}/send` | Contractor | `InvoiceController@send` |
| `GET` | `/api/contractor/leads` | Contractor | `ContractorPortalController@leads` |
| `GET` | `/api/contractor/leads/{id}` | Contractor | `ContractorPortalController@leadDetails` |
| `POST` | `/api/contractor/leads/{id}/decline` | Contractor | `ContractorPortalController@declineLead` |
| `POST` | `/api/contractor/leads/{id}/send-quote` | Contractor | `ContractorPortalController@sendQuote` |
| `GET` | `/api/contractor/messages` | Contractor | `MessageController@index` |
| `GET` | `/api/contractor/messages/{id}` | Contractor | `MessageController@show` |
| `POST` | `/api/contractor/messages/{id}` | Contractor | `MessageController@sendMessage` |
| `GET` | `/api/contractor/products` | Contractor | `ProductController@index` |
| `POST` | `/api/contractor/products` | Contractor | `ProductController@store` |
| `PUT` | `/api/contractor/products/{id}` | Contractor | `ProductController@update` |
| `DELETE` | `/api/contractor/products/{id}` | Contractor | `ProductController@destroy` |
| `POST` | `/api/contractor/products/{id}/toggle` | Contractor | `ProductController@toggle` |
| `GET` | `/api/contractor/profile` | Contractor | `ContractorPortalController@getProfile` |
| `PUT` | `/api/contractor/profile` | Contractor | `ContractorPortalController@updateProfile` |
| `GET` | `/api/contractor/projects` | Contractor | `ContractorPortalController@projects` |
| `POST` | `/api/contractor/projects` | Contractor | `ContractorPortalController@storeProject` |
| `GET` | `/api/contractor/projects/{id}` | Contractor | `ContractorPortalController@projectDetails` |
| `PUT` | `/api/contractor/projects/{id}/progress` | Contractor | `ContractorPortalController@updateProjectProgress` |
| `GET` | `/api/contractor/reviews` | Contractor | `ContractorPortalController@reviews` |
| `POST` | `/api/contractor/reviews/{id}/reply` | Contractor | `ContractorPortalController@replyReview` |
| `POST` | `/api/contractor/reviews/{id}/toggle-feature` | Contractor | `ContractorPortalController@toggleFeatureReview` |
| `GET` | `/api/contractor/services` | Contractor | `ServiceController@index` |
| `POST` | `/api/contractor/services` | Contractor | `ServiceController@store` |
| `PUT` | `/api/contractor/services/{id}` | Contractor | `ServiceController@update` |
| `DELETE` | `/api/contractor/services/{id}` | Contractor | `ServiceController@destroy` |
| `POST` | `/api/contractor/services/{id}/toggle` | Contractor | `ServiceController@toggle` |
| `GET` | `/api/contractor/subscription` | Contractor | `SubscriptionController@show` |
| `POST` | `/api/contractor/subscription/change` | Contractor | `SubscriptionController@change` |
| `GET` | `/api/contractor/subscription/receipts/{id}` | Contractor | `SubscriptionController@receipt` |
| `POST` | `/api/contractor/toggle-availability` | Contractor | `ContractorPortalController@toggleAvailability` |
| `GET` | `/api/contractor/website` | Contractor | `WebsiteController@show` |
| `PUT` | `/api/contractor/website` | Contractor | `WebsiteController@update` |

### Messages

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/messages` | Logged in | `MessageController@index` |
| `POST` | `/api/messages/start` | Logged in | `MessageController@start` |
| `GET` | `/api/messages/unread-count` | Logged in | `MessageController@unreadCount` |
| `GET` | `/api/messages/{id}` | Logged in | `MessageController@show` |
| `POST` | `/api/messages/{id}` | Logged in | `MessageController@sendMessage` |

### Notifications

| Method | Endpoint | Access | Handler |
|---|---|---|---|
| `GET` | `/api/notifications` | Logged in | `NotificationController@index` |
| `POST` | `/api/notifications/mark-all-read` | Logged in | `NotificationController@markAllAsRead` |
| `DELETE` | `/api/notifications/{id}` | Logged in | `NotificationController@destroy` |
| `POST` | `/api/notifications/{id}/read` | Logged in | `NotificationController@markAsRead` |

## Frontend screen → endpoint map

| Screen | Endpoints |
|---|---|
| Header "Log In" | `POST /auth/login`, `GET /auth/me` |
| Footer, Contact page | `GET /site-settings`, `GET /contact` |
| Privacy Policy / Terms / About | `GET /pages/privacy-policy`, `/pages/terms-of-service`, `/pages/about` |
| Home | `GET /home`, `GET /banners`, `GET /home/search` |
| Category / Contractor list / Profile | `GET /categories`, `GET /contractors`, `GET /contractors/{id}` |
| Marketplace | `GET /marketplace`, `GET /marketplace/products`, `GET /marketplace/products/{id}` |
| Request a Quote | `GET /quotes/wizard-config`, `POST /quotes` |
| Join as a Pro | `POST /business/onboarding/*`, `GET /membership-plans` |
| Customer dashboard | `/customer/*`, `/messages/*`, `/notifications/*`, `/account/*` |
| Contractor: Dashboard | `GET /contractor/dashboard` |
| Contractor: Quote Requests | `GET /contractor/leads`, `POST /contractor/leads/{id}/send-quote` |
| Contractor: Projects | `GET /contractor/projects` |
| Contractor: Messages | `GET /messages`, `POST /messages/{id}` |
| Contractor: Invoices / New Invoice / Invoice detail | `/contractor/invoices*`, `/contractor/customers` |
| Contractor: My Services / Products | `/contractor/services*`, `/contractor/products*` |
| Contractor: Gallery / Banner / Website | `/contractor/gallery*`, `/contractor/banner`, `/contractor/website` |
| Contractor: Business Profile | `GET/PUT /contractor/profile`, `/contractor/documents*` |
| Contractor: Reviews | `GET /contractor/reviews`, `POST /contractor/reviews/{id}/reply` |
| Contractor: Subscription | `/contractor/subscription*` |
| Contractor: Settings | `/account/*`, `POST /auth/password` |
| Notifications bell | `GET /notifications`, `POST /notifications/mark-all-read` |
