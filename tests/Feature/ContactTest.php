<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_contact_info(): void
    {
        $response = $this->getJson('/api/contact');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'hero' => [
                        'headline',
                        'subtitle',
                    ],
                    'support_details' => [
                        'email',
                        'phone',
                        'phone_display',
                        'address',
                        'office_hours',
                        'map_location',
                    ],
                    'user_prefill',
                    'breadcrumbs',
                ],
                'code',
            ]);

        $this->assertEquals('support@valorhub.com', $response->json('data.support_details.email'));
    }

    public function test_can_submit_contact_message(): void
    {
        $payload = [
            'full_name' => 'Michael Scott',
            'email' => 'michael@dunder.test',
            'subject' => 'Contractor Verification Question',
            'message' => 'Hello, I would like to verify my ID.me credentials for my roofing business.',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.full_name', 'Michael Scott')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'michael@dunder.test',
            'subject' => 'Contractor Verification Question',
            'status' => 'pending',
        ]);
    }

    public function test_validation_errors_on_empty_contact_submission(): void
    {
        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'email', 'subject', 'message']);
    }
}
