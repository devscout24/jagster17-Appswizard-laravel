<?php

namespace Tests\Feature;

use App\Models\BillingHistory;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseAndModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_roles_and_assign_to_users(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $bizRole = Role::create(['name' => 'business', 'guard_name' => 'api']);
        $custRole = Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $businessUser = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'phone' => '+15551234567',
            'avatar' => 'avatars/marcus.jpg',
            'status' => 'active',
        ]);
        $businessUser->assignRole($bizRole);

        $customerUser = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'phone' => '+15559876543',
            'avatar' => null,
            'status' => 'active',
        ]);
        $customerUser->assignRole($custRole);

        $this->assertTrue($businessUser->hasRole('business', 'api'));
        $this->assertTrue($customerUser->hasRole('customer', 'api'));
        $this->assertEquals('business', $businessUser->getJWTCustomClaims()['role']);
    }

    public function test_full_marketplace_data_model_lifecycle(): void
    {
        // 1. Users & Profiles
        $businessUser = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'phone' => '1234567890',
            'status' => 'active',
        ]);

        $bizProfile = BusinessProfile::create([
            'user_id' => $businessUser->id,
            'business_name' => 'Iron Ridge Co.',
            'city' => 'Austin',
            'state' => 'TX',
            'member_since' => '2022-01-15',
            'is_elite' => true,
            'is_veteran_owned' => true,
            'is_id_verified' => true,
            'avg_rating' => 4.95,
            'review_count' => 48,
            'bio' => 'Top rated general contractor.',
            'website_url' => 'https://ironridge.test',
        ]);

        $customerUser = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'phone' => '0987654321',
            'status' => 'active',
        ]);

        $custProfile = CustomerProfile::create([
            'user_id' => $customerUser->id,
            'city' => 'Austin',
            'state' => 'TX',
        ]);

        $this->assertNotNull($businessUser->businessProfile);
        $this->assertEquals('Iron Ridge Co.', $businessUser->businessProfile->business_name);
        $this->assertNotNull($customerUser->customerProfile);

        // 2. Categories, Services, Products
        $serviceCat = Category::create(['name' => 'Remodeling', 'type' => 'service']);
        $productCat = Category::create(['name' => 'Tools', 'type' => 'product']);

        $service = Service::create([
            'business_id' => $businessUser->id,
            'category_id' => $serviceCat->id,
            'name' => 'Kitchen Remodel',
            'description' => 'Full kitchen upgrade',
            'pricing_type' => 'fixed',
            'price' => 5000.00,
            'unit' => '/project',
            'status' => 'active',
        ]);

        $product = Product::create([
            'business_id' => $businessUser->id,
            'category_id' => $productCat->id,
            'name' => 'Heavy Duty Drill',
            'description' => 'Pro grade hammer drill',
            'price' => 199.99,
            'unit' => 'piece',
            'status' => 'active',
        ]);

        $this->assertCount(1, $businessUser->services);
        $this->assertCount(1, $businessUser->products);
        $this->assertEquals($serviceCat->id, $service->category->id);

        // 3. Quote Request & Project
        $quote = QuoteRequest::create([
            'business_id' => $businessUser->id,
            'customer_id' => $customerUser->id,
            'category_id' => $serviceCat->id,
            'project_title' => 'Master Bath Remodel',
            'description' => 'Need marble tile and vanity installed',
            'budget_min' => 3000,
            'budget_max' => 6000,
            'city' => 'Austin',
            'state' => 'TX',
            'status' => 'accepted',
            'requested_at' => now(),
        ]);

        $project = Project::create([
            'business_id' => $businessUser->id,
            'customer_id' => $customerUser->id,
            'quote_request_id' => $quote->id,
            'title' => 'Master Bath Remodel',
            'due_date' => '2026-10-15',
            'progress_percent' => 50,
            'status' => 'in_progress',
        ]);

        $this->assertEquals($quote->id, $project->quoteRequest->id);
        $this->assertCount(1, $businessUser->projects);

        // 4. Invoice
        $invoice = Invoice::create([
            'business_id' => $businessUser->id,
            'customer_id' => $customerUser->id,
            'project_id' => $project->id,
            'invoice_number' => 'INV-2041',
            'amount' => 4500.00,
            'issued_at' => '2026-09-13',
            'due_at' => '2026-09-30',
            'status' => 'pending',
        ]);

        $this->assertEquals('INV-2041', $project->invoices->first()->invoice_number);

        // 5. Conversation & Message
        $conversation = Conversation::create([
            'business_id' => $businessUser->id,
            'customer_id' => $customerUser->id,
            'last_message_at' => now(),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customerUser->id,
            'body' => 'Hi Marcus, when can you start?',
        ]);

        $this->assertCount(1, $conversation->messages);
        $this->assertEquals($customerUser->id, $message->sender->id);

        // 6. Review
        $review = Review::create([
            'business_id' => $businessUser->id,
            'customer_id' => $customerUser->id,
            'project_id' => $project->id,
            'rating' => 5,
            'comment' => 'Outstanding work!',
        ]);

        $this->assertCount(1, $businessUser->reviews);

        // 7. Subscriptions & Billing History
        $plan = SubscriptionPlan::create([
            'name' => 'Elite',
            'monthly_price' => 99.00,
            'annual_price' => 990.00,
            'features' => ['unlimited_leads' => true, 'priority_listing' => true],
        ]);

        $subscription = Subscription::create([
            'business_id' => $businessUser->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'payment_method_last4' => '4242',
            'renews_at' => '2026-10-13',
        ]);

        $billing = BillingHistory::create([
            'subscription_id' => $subscription->id,
            'plan_name' => 'Elite',
            'amount' => 99.00,
            'status' => 'paid',
            'receipt_url' => 'https://receipts.test/rec-123.pdf',
            'billed_at' => '2026-09-13',
        ]);

        $this->assertNotNull($businessUser->subscription);
        $this->assertEquals('Elite', $businessUser->subscription->plan->name);
        $this->assertCount(1, $subscription->billingHistories);

        // 8. Notifications
        $notification = Notification::create([
            'user_id' => $businessUser->id,
            'type' => 'quote_request',
            'title' => 'New Quote Request',
            'body' => 'Sarah submitted a new quote request.',
        ]);

        $this->assertCount(1, $businessUser->notifications);
    }
}
