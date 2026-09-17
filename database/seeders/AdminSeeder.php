<?php

namespace Database\Seeders;

use App\Models\BillingHistory;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\CustomerPayment;
use App\Models\CustomerProfile;
use App\Models\Faq;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\VeteranNomination;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Setup Roles
        $roles = ['super_admin', 'admin', 'business', 'customer'];
        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
            Role::findOrCreate($roleName, 'api');
        }

        // 2. Setup Permissions
        $permissions = [
            'view_dashboard',
            'manage_contractors',
            'manage_customers',
            'manage_categories',
            'manage_catalog',
            'manage_quotes',
            'manage_projects',
            'manage_invoices',
            'manage_subscriptions',
            'moderate_reviews',
            'manage_nominations',
            'manage_support',
            'manage_faqs',
            'manage_admins',
            'edit_profile',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
            Permission::findOrCreate($permissionName, 'api');
        }

        // Assign all permissions to super_admin and admin
        $superAdminRoleWeb = Role::findByName('super_admin', 'web');
        $superAdminRoleApi = Role::findByName('super_admin', 'api');
        $superAdminRoleWeb->syncPermissions(Permission::where('guard_name', 'web')->get());
        $superAdminRoleApi->syncPermissions(Permission::where('guard_name', 'api')->get());

        $adminRoleWeb = Role::findByName('admin', 'web');
        $adminRoleApi = Role::findByName('admin', 'api');
        $adminRoleWeb->syncPermissions(Permission::where('guard_name', 'web')->get());
        $adminRoleApi->syncPermissions(Permission::where('guard_name', 'api')->get());

        // 3. Create Super Admin User
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@valorhub.com'],
            [
                'name' => 'Chief Administrator',
                'password' => Hash::make('password'),
                'phone' => '+1 (555) 019-2834',
                'avatar' => 'assets/images/users/user-1.jpg',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        // Also create a secondary demo Admin
        $opsAdmin = User::updateOrCreate(
            ['email' => 'operations@valorhub.com'],
            [
                'name' => 'Sarah Jenkins',
                'password' => Hash::make('password'),
                'phone' => '+1 (555) 443-8899',
                'avatar' => 'assets/images/users/user-2.jpg',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $opsAdmin->assignRole('admin');

        // 4. Seed Subscription Plans if not present
        if (SubscriptionPlan::count() === 0) {
            $this->call(SubscriptionPlanSeeder::class);
        }

        // 5. Seed Categories if needed
        $categoriesData = [
            ['name' => 'Roofing & Siding', 'type' => 'service', 'icon' => 'ti ti-home', 'description' => 'Residential & commercial roof repairs, inspections, and installations.'],
            ['name' => 'HVAC & Climate Control', 'type' => 'service', 'icon' => 'ti ti-air-conditioning', 'description' => 'Heating, ventilation, and air conditioning maintenance & installations.'],
            ['name' => 'Electrical Services', 'type' => 'service', 'icon' => 'ti ti-bolt', 'description' => 'Licensed electrical panels, rewiring, lighting, and surge protection.'],
            ['name' => 'Plumbing & Pipes', 'type' => 'service', 'icon' => 'ti ti-droplet', 'description' => 'Pipe repair, water heater installs, drain clearing, and bathroom rough-ins.'],
            ['name' => 'Landscaping & Masonry', 'type' => 'service', 'icon' => 'ti ti-plant', 'description' => 'Lawn care, retaining walls, hardscaping, and tree trimming.'],
            ['name' => 'Painting & Finishing', 'type' => 'service', 'icon' => 'ti ti-paint', 'description' => 'Interior and exterior painting, staining, and drywall repairs.'],
            ['name' => 'General Contracting', 'type' => 'service', 'icon' => 'ti ti-hammer', 'description' => 'Complete home remodeling, extensions, framing, and permits.'],
            ['name' => 'HVAC Parts & Filters', 'type' => 'product', 'icon' => 'ti ti-box', 'description' => 'Replacement filters, thermostat units, and HVAC equipment.'],
            ['name' => 'Electrical & Lighting Gear', 'type' => 'product', 'icon' => 'ti ti-bulb', 'description' => 'Commercial grade LED fixtures, wiring tools, and panels.'],
            ['name' => 'Roofing Tools & Materials', 'type' => 'product', 'icon' => 'ti ti-tool', 'description' => 'Flashing, shingles, safety harnesses, and sealants.'],
        ];

        foreach ($categoriesData as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }

        // 6. Create Demo Contractors (Businesses)
        $contractorsData = [
            [
                'name' => 'Apex Patriot Roofing LLC',
                'email' => 'contact@apexpatriot.test',
                'owner' => 'Marcus Vance',
                'phone' => '+1 (480) 555-0142',
                'city' => 'Phoenix',
                'state' => 'AZ',
                'zip' => '85001',
                'is_elite' => true,
                'is_veteran_owned' => true,
                'is_id_verified' => true,
                'is_license_verified' => true,
                'license' => 'ROC-329841',
                'exp' => 14,
                'rate' => 125.00,
                'rating' => 4.9,
                'reviews' => 48,
                'bio' => 'USMC Veteran-owned residential and commercial roofing specialist serving greater Phoenix for 14 years.',
            ],
            [
                'name' => 'Valor Electric & Solar',
                'email' => 'service@valorelectric.test',
                'owner' => 'David Martinez',
                'phone' => '+1 (512) 555-0188',
                'city' => 'Austin',
                'state' => 'TX',
                'zip' => '78701',
                'is_elite' => true,
                'is_veteran_owned' => true,
                'is_id_verified' => true,
                'is_license_verified' => true,
                'license' => 'TECL-99214',
                'exp' => 11,
                'rate' => 110.00,
                'rating' => 4.8,
                'reviews' => 36,
                'bio' => 'Master Electrician veteran providing commercial panel upgrades, whole-home battery backup, and EV charging.',
            ],
            [
                'name' => 'Blue Star Plumbing Pros',
                'email' => 'info@bluestarplumbing.test',
                'owner' => 'Robert Taylor',
                'phone' => '+1 (720) 555-0199',
                'city' => 'Denver',
                'state' => 'CO',
                'zip' => '80202',
                'is_elite' => false,
                'is_veteran_owned' => true,
                'is_id_verified' => true,
                'is_license_verified' => true,
                'license' => 'CO-PLM-8472',
                'exp' => 8,
                'rate' => 95.00,
                'rating' => 4.7,
                'reviews' => 24,
                'bio' => 'Dependable plumbing solutions from minor leak fixes to full water heater replacements.',
            ],
            [
                'name' => 'Summit HVAC Solutions',
                'email' => 'team@summithvac.test',
                'owner' => 'Elena Rostova',
                'phone' => '+1 (206) 555-0112',
                'city' => 'Seattle',
                'state' => 'WA',
                'zip' => '98101',
                'is_elite' => true,
                'is_veteran_owned' => false,
                'is_id_verified' => true,
                'is_license_verified' => true,
                'license' => 'WA-HVAC-1029',
                'exp' => 16,
                'rate' => 135.00,
                'rating' => 4.95,
                'reviews' => 62,
                'bio' => 'Premier climate control, heat pump systems, and clean air filtration installations.',
            ],
        ];

        $seededBusinesses = [];
        $planElite = SubscriptionPlan::where('slug', 'elite')->first() ?? SubscriptionPlan::first();

        foreach ($contractorsData as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['owner'],
                    'password' => Hash::make('password'),
                    'phone' => $c['phone'],
                    'avatar' => 'assets/images/users/user-' . rand(3, 8) . '.jpg',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('business');

            BusinessProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $c['name'],
                    'owner_name' => $c['owner'],
                    'business_type' => 'LLC',
                    'phone_number' => $c['phone'],
                    'tax_id' => 'XX-XXX' . rand(1000, 9999),
                    'city' => $c['city'],
                    'state' => $c['state'],
                    'zip_code' => $c['zip'],
                    'years_experience' => $c['exp'],
                    'member_since' => now()->subMonths(rand(6, 36)),
                    'is_elite' => $c['is_elite'],
                    'is_veteran_owned' => $c['is_veteran_owned'],
                    'is_id_verified' => $c['is_id_verified'],
                    'is_available_today' => true,
                    'is_license_verified' => $c['is_license_verified'],
                    'license_number' => $c['license'],
                    'business_hours' => 'Mon - Sat: 7:00 AM - 6:00 PM',
                    'service_radius' => 50,
                    'hourly_rate' => $c['rate'],
                    'avg_rating' => $c['rating'],
                    'review_count' => $c['reviews'],
                    'bio' => $c['bio'],
                    'onboarding_completed' => true,
                    'onboarding_step' => 5,
                    'terms_accepted_at' => now()->subMonths(10),
                    'id_me_verified_at' => $c['is_id_verified'] ? now()->subMonths(8) : null,
                ]
            );

            if ($planElite) {
                $sub = Subscription::updateOrCreate(
                    ['business_id' => $user->id],
                    [
                        'plan_id' => $planElite->id,
                        'billing_cycle' => 'monthly',
                        'status' => 'active',
                        'payment_method_last4' => '4242',
                        'renews_at' => now()->addMonth(),
                    ]
                );

                BillingHistory::updateOrCreate(
                    ['subscription_id' => $sub->id, 'billed_at' => now()->subMonth()->toDateString()],
                    [
                        'plan_name' => $planElite->name,
                        'amount' => $planElite->monthly_price,
                        'status' => 'paid',
                        'receipt_url' => 'https://valorhub.test/receipts/' . Str::random(12),
                        'billed_at' => now()->subMonth(),
                    ]
                );
            }

            $seededBusinesses[] = $user;
        }

        // 7. Create Demo Customers
        $customersData = [
            ['name' => 'Michael Chang', 'email' => 'michael.chang@test.com', 'city' => 'Phoenix', 'state' => 'AZ'],
            ['name' => 'Jessica Holloway', 'email' => 'jessica.holloway@test.com', 'city' => 'Austin', 'state' => 'TX'],
            ['name' => 'Daniel Miller', 'email' => 'daniel.miller@test.com', 'city' => 'Denver', 'state' => 'CO'],
            ['name' => 'Karen Davis', 'email' => 'karen.davis@test.com', 'city' => 'Seattle', 'state' => 'WA'],
        ];

        $seededCustomers = [];
        foreach ($customersData as $cust) {
            $user = User::updateOrCreate(
                ['email' => $cust['email']],
                [
                    'name' => $cust['name'],
                    'password' => Hash::make('password'),
                    'phone' => '+1 (555) ' . rand(100, 999) . '-' . rand(1000, 9999),
                    'avatar' => 'assets/images/users/user-' . rand(5, 10) . '.jpg',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $user->assignRole('customer');

            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'city' => $cust['city'],
                    'state' => $cust['state'],
                ]
            );

            $seededCustomers[] = $user;
        }

        // 8. Seed Services
        $serviceCategories = Category::where('type', 'service')->get();
        if ($serviceCategories->isNotEmpty() && !empty($seededBusinesses)) {
            foreach ($seededBusinesses as $biz) {
                Service::updateOrCreate(
                    ['business_id' => $biz->id, 'name' => 'Full Diagnostic Inspection'],
                    [
                        'category_id' => $serviceCategories->random()->id,
                        'description' => 'Comprehensive site inspection, damage assessment report, and transparent cost estimate.',
                        'pricing_type' => 'fixed',
                        'price' => 149.00,
                        'unit' => 'per inspection',
                        'status' => 'active',
                    ]
                );

                Service::updateOrCreate(
                    ['business_id' => $biz->id, 'name' => 'Standard Installation & Maintenance'],
                    [
                        'category_id' => $serviceCategories->random()->id,
                        'description' => 'Professional installation by certified technicians including 1-year labor warranty.',
                        'pricing_type' => 'hourly',
                        'price' => 115.00,
                        'unit' => 'per hour',
                        'status' => 'active',
                    ]
                );
            }
        }

        // 9. Seed Products
        $prodCategories = Category::where('type', 'product')->get();
        if ($prodCategories->isNotEmpty() && !empty($seededBusinesses)) {
            $sampleProducts = [
                ['name' => 'Smart WiFi Digital Thermostat Pro', 'price' => 249.99, 'sku' => 'PRD-THM-991', 'stock' => 25],
                ['name' => 'Heavy-Duty Commercial Surge Protector', 'price' => 189.50, 'sku' => 'PRD-SRG-404', 'stock' => 18],
                ['name' => 'Industrial Composite Roof Shingles (Bundle)', 'price' => 84.00, 'sku' => 'PRD-SHG-102', 'stock' => 120],
                ['name' => 'Tankless Water Heater Filter Kit', 'price' => 65.00, 'sku' => 'PRD-FLT-707', 'stock' => 40],
            ];

            foreach ($sampleProducts as $i => $p) {
                Product::updateOrCreate(
                    ['sku' => $p['sku']],
                    [
                        'business_id' => $seededBusinesses[$i % count($seededBusinesses)]->id,
                        'category_id' => $prodCategories->random()->id,
                        'name' => $p['name'],
                        'description' => 'High-performance commercial equipment built to last with full manufacturer guarantee.',
                        'image' => 'assets/images/products/product-' . (($i % 6) + 1) . '.png',
                        'price' => $p['price'],
                        'unit' => 'each',
                        'is_elite_tier' => $i % 2 === 0,
                        'is_trending' => true,
                        'in_stock' => true,
                        'stock_quantity' => $p['stock'],
                        'warranty' => '3 Years Manufacturer Warranty',
                        'shipping_info' => 'Free 2-day domestic delivery',
                        'rating' => 4.85,
                        'review_count' => rand(12, 45),
                        'features' => ['High Energy Efficiency', 'Smart App Integration', 'Industrial Grade'],
                        'status' => 'active',
                    ]
                );
            }
        }

        // 10. Seed Quotes, Projects, Invoices & Payments
        if (!empty($seededBusinesses) && !empty($seededCustomers)) {
            $quote1 = QuoteRequest::updateOrCreate(
                ['reference_number' => 'VH-202609-001'],
                [
                    'business_id' => $seededBusinesses[0]->id,
                    'customer_id' => $seededCustomers[0]->id,
                    'category_id' => $serviceCategories->first()?->id,
                    'project_title' => 'Complete Architectural Shingle Replacement',
                    'description' => 'Severe hail storm damage to northern slope. Needs tear-off, synthetic underlayment, and Class 4 impact shingles.',
                    'budget_range' => '5000_15000',
                    'budget_min' => 8000,
                    'budget_max' => 14000,
                    'timeline' => 'within_1_week',
                    'street_address' => '4421 E Camelback Rd',
                    'zip_code' => '85018',
                    'city' => 'Phoenix',
                    'state' => 'AZ',
                    'quote_amount' => 11250.00,
                    'labor_cost' => 4500.00,
                    'materials_cost' => 6200.00,
                    'tax_cost' => 550.00,
                    'estimated_duration' => '3 Days',
                    'contractor_notes' => 'Materials scheduled for delivery Monday morning. Full crew assigned.',
                    'status' => 'accepted',
                    'requested_at' => now()->subDays(12),
                ]
            );

            $quote2 = QuoteRequest::updateOrCreate(
                ['reference_number' => 'VH-202609-002'],
                [
                    'business_id' => $seededBusinesses[1]->id,
                    'customer_id' => $seededCustomers[1]->id,
                    'category_id' => $serviceCategories->skip(1)->first()?->id,
                    'project_title' => '200A Service Panel Upgrade & EV Charger',
                    'description' => 'Upgrading existing 100A fuse box to 200A breaker panel with Level 2 NEMA 14-50 garage outlet.',
                    'budget_range' => '1000_5000',
                    'budget_min' => 2500,
                    'budget_max' => 4500,
                    'timeline' => 'flexible',
                    'street_address' => '1204 Barton Springs Rd',
                    'zip_code' => '78704',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'quote_amount' => 3400.00,
                    'labor_cost' => 1800.00,
                    'materials_cost' => 1400.00,
                    'tax_cost' => 200.00,
                    'status' => 'quoted',
                    'requested_at' => now()->subDays(5),
                ]
            );

            $project1 = Project::updateOrCreate(
                ['quote_request_id' => $quote1->id],
                [
                    'business_id' => $seededBusinesses[0]->id,
                    'customer_id' => $seededCustomers[0]->id,
                    'title' => 'Complete Architectural Shingle Replacement',
                    'description' => 'Full tear-off and replacement with Class 4 hail-resistant shingles.',
                    'total_amount' => 11250.00,
                    'start_date' => now()->subDays(8),
                    'due_date' => now()->addDays(2),
                    'progress_percent' => 75,
                    'status' => 'in_progress',
                ]
            );

            $inv1 = Invoice::updateOrCreate(
                ['invoice_number' => 'INV-2026-0891'],
                [
                    'business_id' => $seededBusinesses[0]->id,
                    'customer_id' => $seededCustomers[0]->id,
                    'project_id' => $project1->id,
                    'amount' => 5625.00,
                    'labor_amount' => 2250.00,
                    'materials_amount' => 3100.00,
                    'platform_fee' => 275.00,
                    'notes' => '50% Initial Deposit upon project commencement.',
                    'issued_at' => now()->subDays(8),
                    'due_at' => now()->subDays(1),
                    'paid_at' => now()->subDays(7),
                    'status' => 'paid',
                ]
            );

            CustomerPayment::updateOrCreate(
                ['invoice_id' => $inv1->id],
                [
                    'user_id' => $seededCustomers[0]->id,
                    'business_id' => $seededBusinesses[0]->id,
                    'transaction_id' => 'TXN-STRIPE-' . Str::upper(Str::random(10)),
                    'amount' => 5625.00,
                    'payment_method' => 'Visa ending in 4242',
                    'status' => 'paid',
                    'receipt_url' => 'https://valorhub.test/payments/rec_' . Str::random(16),
                    'paid_at' => now()->subDays(7),
                ]
            );

            // Review
            Review::updateOrCreate(
                ['project_id' => $project1->id],
                [
                    'business_id' => $seededBusinesses[0]->id,
                    'customer_id' => $seededCustomers[0]->id,
                    'rating' => 5,
                    'comment' => 'Exceptional service! Marcus and his crew arrived promptly at 7 AM, protected our landscaping, and completed the work cleanly.',
                    'contractor_reply' => 'Thank you Michael! It was our pleasure protecting your home. Let us know if you need anything in the future.',
                    'replied_at' => now()->subDays(5),
                    'is_featured' => true,
                ]
            );
        }

        // 11. Seed Veteran Nominations
        VeteranNomination::updateOrCreate(
            ['nominee_name' => 'Sgt. Thomas Briggs'],
            [
                'user_id' => $seededCustomers[0]->id ?? null,
                'nominator_name' => 'Sarah Briggs',
                'nominator_email' => 'sarah.briggs@test.com',
                'nominator_phone' => '+1 (480) 555-8811',
                'nominee_branch' => 'U.S. Marine Corps',
                'nominee_city' => 'Mesa',
                'nominee_state' => 'AZ',
                'project_needed' => 'Wheelchair Ramp & ADA Bathroom Modification',
                'story_details' => 'Sgt. Briggs was injured during deployment in Fallujah. He recently had mobility surgery and needs an accessible front entry ramp and roll-in shower.',
                'status' => 'reviewing',
            ]
        );

        VeteranNomination::updateOrCreate(
            ['nominee_name' => 'Captain Arthur Vance'],
            [
                'user_id' => $seededCustomers[1]->id ?? null,
                'nominator_name' => 'Lt. Eric Cooper',
                'nominator_email' => 'cooper.e@test.com',
                'nominator_phone' => '+1 (512) 555-4422',
                'nominee_branch' => 'U.S. Army (Purple Heart)',
                'nominee_city' => 'Killeen',
                'nominee_state' => 'TX',
                'project_needed' => 'Roof Leak & Ceiling Structural Repair',
                'story_details' => 'Captain Vance is a retired veteran whose roof suffered major hail leaks resulting in water damage across his living room.',
                'status' => 'approved',
            ]
        );

        // 12. Seed Support Contact Messages
        ContactMessage::updateOrCreate(
            ['email' => 'mark.shelton@contractorcorp.test'],
            [
                'full_name' => 'Mark Shelton',
                'subject' => 'Business Verification Badge Inquiry',
                'message' => 'Hello team, I submitted my state license ROC-449102 two days ago and wanted to confirm the remaining steps for the verified badge.',
                'status' => 'pending',
            ]
        );

        ContactMessage::updateOrCreate(
            ['email' => 'alicia.wong@homeowner.test'],
            [
                'full_name' => 'Alicia Wong',
                'subject' => 'Question regarding escrow payment guarantees',
                'message' => 'Hi, how does the platform protection work when paying invoice milestones for home renovations?',
                'status' => 'in_progress',
            ]
        );

        // 13. Seed FAQs if empty
        if (Faq::count() === 0) {
            $defaultFaqs = [
                ['category' => 'customers', 'question' => 'How do I request a quote from a verified contractor?', 'answer' => 'Search by category or zip code, browse contractor badges, and submit your project specifications using our simple 5-step quote wizard.', 'sort_order' => 1],
                ['category' => 'customers', 'question' => 'What is the ValorHub Veteran Guarantee?', 'answer' => 'ValorHub supports veteran-owned contractors and dedicates a portion of every completed invoice fee directly to veteran home repair grants.', 'sort_order' => 2],
                ['category' => 'contractors', 'question' => 'How do I obtain the Verified and Elite badges?', 'answer' => 'Upload your valid state contractor license, complete identity verification via ID.me, and maintain at least a 4.8 client satisfaction rating.', 'sort_order' => 3],
                ['category' => 'payments', 'question' => 'How are project invoices and milestone payouts handled?', 'answer' => 'Contractors issue itemized digital invoices with labor and materials breakdown. Homeowners pay securely via Stripe card or ACH.', 'sort_order' => 4],
            ];

            foreach ($defaultFaqs as $faq) {
                Faq::create(array_merge($faq, ['is_published' => true]));
            }
        }
    }
}
