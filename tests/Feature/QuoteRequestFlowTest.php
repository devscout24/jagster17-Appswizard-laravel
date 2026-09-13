<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_wizard_configuration(): void
    {
        $contractor = User::create([
            'name' => 'Apex Roofing',
            'email' => 'apex@roofing.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $contractor->id,
            'business_name' => 'Apex Roofing & Construction',
            'city' => 'Dallas',
            'state' => 'TX',
            'member_since' => '2021-01-01',
            'is_elite' => true,
            'is_veteran_owned' => true,
        ]);

        $response = $this->getJson('/api/quotes/wizard-config?contractor_id='.$contractor->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'steps' => [
                        '*' => ['step', 'key', 'title', 'subtitle'],
                    ],
                    'service_options' => [
                        '*' => ['key', 'name', 'icon', 'category_id'],
                    ],
                    'budget_options',
                    'timeline_options',
                    'target_contractor' => [
                        'id',
                        'business_name',
                        'is_elite',
                        'is_veteran_owned',
                    ],
                ],
                'code',
            ]);

        $this->assertEquals('Apex Roofing & Construction', $response->json('data.target_contractor.business_name'));
        $this->assertEquals(5, count($response->json('data.steps')));
        $this->assertEquals(8, count($response->json('data.service_options')));
    }

    public function test_can_submit_quote_request_through_wizard(): void
    {
        $category = Category::create([
            'name' => 'Roofing',
            'type' => 'service',
        ]);

        $contractor = User::create([
            'name' => 'Marcus Rivera',
            'email' => 'marcus@ironridge.test',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        BusinessProfile::create([
            'user_id' => $contractor->id,
            'business_name' => 'Iron Ridge Co.',
            'city' => 'Austin',
            'state' => 'TX',
            'member_since' => '2021-01-01',
            'is_elite' => true,
        ]);

        $customer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $payload = [
            'category_id' => $category->id,
            'contractor_id' => $contractor->id,
            'project_title' => 'Complete Shingle Roof Replacement',
            'description' => 'Our 2,400 sq ft asphalt shingle roof has storm damage and needs complete replacement.',
            'attachments' => ['https://storage.test/roof1.jpg', 'https://storage.test/roof2.jpg'],
            'budget_range' => '5000_15000',
            'timeline' => 'within_1_month',
            'street_address' => '742 Evergreen Terrace',
            'zip_code' => '78701',
            'city' => 'Austin',
            'state' => 'TX',
        ];

        $token = auth('api')->login($customer);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/quotes', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'reference_number',
                    'project_title',
                    'service_type',
                    'description',
                    'attachments',
                    'budget' => [
                        'range_key',
                        'display',
                        'min',
                        'max',
                    ],
                    'timeline' => [
                        'key',
                        'display',
                    ],
                    'location' => [
                        'street_address',
                        'city',
                        'state',
                        'zip_code',
                        'display',
                    ],
                    'recipient' => [
                        'id',
                        'business_name',
                    ],
                    'status',
                    'confirmation' => [
                        'title',
                        'message',
                        'reference_badge',
                    ],
                    'requested_at',
                ],
                'code',
            ]);

        $this->assertEquals('Complete Shingle Roof Replacement', $response->json('data.project_title'));
        $this->assertEquals('Iron Ridge Co.', $response->json('data.recipient.business_name'));
        $this->assertStringStartsWith('VH-', $response->json('data.reference_number'));
        $this->assertEquals(5000, $response->json('data.budget.min'));
        $this->assertEquals(15000, $response->json('data.budget.max'));

        $this->assertDatabaseHas('quote_requests', [
            'customer_id' => $customer->id,
            'business_id' => $contractor->id,
            'city' => 'Austin',
            'status' => 'new',
        ]);
    }

    public function test_can_view_single_quote_request(): void
    {
        $customer = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah2@test.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);

        $quote = QuoteRequest::create([
            'reference_number' => 'VH-20260727-042',
            'customer_id' => $customer->id,
            'project_title' => 'Plumbing Repair',
            'description' => 'Fix main water line leak',
            'budget_range' => 'under_1000',
            'timeline' => 'asap',
            'street_address' => '123 Pine St',
            'zip_code' => '78702',
            'city' => 'Austin',
            'state' => 'TX',
            'status' => 'new',
            'requested_at' => now(),
        ]);

        $response = $this->getJson('/api/quotes/'.$quote->id);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.reference_number', 'VH-20260727-042')
            ->assertJsonPath('data.project_title', 'Plumbing Repair');
    }
}
