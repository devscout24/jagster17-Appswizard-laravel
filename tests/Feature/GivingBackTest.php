<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\VeteranNomination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GivingBackTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_giving_back_screen(): void
    {
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
            'is_veteran_owned' => true,
        ]);

        $response = $this->getJson('/api/giving-back');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'hero' => [
                        'badge',
                        'headline',
                        'highlight',
                        'subtitle',
                    ],
                    'impact_metrics' => [
                        '*' => [
                            'key',
                            'value',
                            'label',
                        ],
                    ],
                    'initiatives' => [
                        '*' => [
                            'id',
                            'icon_symbol',
                            'title',
                            'description',
                        ],
                    ],
                    'testimonial' => [
                        'quote',
                        'author',
                        'author_title',
                        'location',
                        'program',
                    ],
                    'nomination_cta',
                    'breadcrumbs',
                ],
                'code',
            ]);

        $this->assertEquals('Giving back to those who served', $response->json('data.hero.headline'));
        $this->assertEquals(4, count($response->json('data.impact_metrics')));
        $this->assertEquals(3, count($response->json('data.initiatives')));
    }

    public function test_can_submit_veteran_nomination(): void
    {
        $payload = [
            'nominator_name' => 'Sarah Jenkins',
            'nominator_email' => 'sarah@test.com',
            'nominator_phone' => '555-123-4567',
            'nominee_name' => 'Captain Robert Miller',
            'nominee_branch' => 'U.S. Marine Corps',
            'nominee_city' => 'San Diego',
            'nominee_state' => 'CA',
            'project_needed' => 'Wheelchair access ramp and bathroom modification',
            'story_details' => 'Robert served two tours and recently suffered mobility loss. His home entry currently has steep stairs.',
        ];

        $response = $this->postJson('/api/giving-back/nominate', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.nominee_name', 'Captain Robert Miller')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('veteran_nominations', [
            'nominee_name' => 'Captain Robert Miller',
            'nominee_branch' => 'U.S. Marine Corps',
            'status' => 'pending',
        ]);
    }

    public function test_validation_errors_when_submitting_invalid_nomination(): void
    {
        $response = $this->postJson('/api/giving-back/nominate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nominator_name', 'nominator_email', 'nominee_name', 'project_needed', 'story_details']);
    }
}
