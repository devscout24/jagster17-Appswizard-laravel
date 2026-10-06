<?php

namespace Tests\Feature\Api;

use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerReviewsAndSiteContentTest extends TestCase
{
    use ApiTestHelpers, RefreshDatabase;

    public function test_customer_reviews_completed_jobs_and_rating_updates(): void
    {
        $customer = $this->makeCustomer();
        $contractor = $this->makeContractor();
        $project = Project::create([
            'business_id' => $contractor->id,
            'customer_id' => $customer->id,
            'title' => 'Deck Installation',
            'status' => 'completed',
            'progress_percent' => 100,
        ]);
        $api = $this->actingAs($customer, 'api');

        $api->getJson('/api/customer/reviews')->assertOk()->assertJsonCount(1, 'data.pending');

        $reviewId = $api->postJson('/api/customer/reviews', [
            'project_id' => $project->id,
            'rating' => 4,
            'comment' => 'Solid work and a fair price.',
        ])->assertCreated()->json('data.id');

        // One review per job
        $api->postJson('/api/customer/reviews', [
            'project_id' => $project->id,
            'rating' => 5,
            'comment' => 'Trying to review twice.',
        ])->assertUnprocessable();

        $api->putJson("/api/customer/reviews/{$reviewId}", ['rating' => 5])->assertOk()->assertJsonPath('data.rating', 5);

        $profile = $contractor->businessProfile->fresh();
        $this->assertSame(1, $profile->review_count);
        $this->assertEquals(5.0, (float) $profile->avg_rating);
        $api->getJson('/api/customer/reviews')->assertOk()->assertJsonCount(0, 'data.pending');
    }

    public function test_cannot_review_an_unfinished_job(): void
    {
        $customer = $this->makeCustomer();
        $project = Project::create([
            'business_id' => $this->makeContractor()->id,
            'customer_id' => $customer->id,
            'title' => 'Kitchen Remodel',
            'status' => 'in_progress',
            'progress_percent' => 40,
        ]);

        $this->actingAs($customer, 'api')->postJson('/api/customer/reviews', [
            'project_id' => $project->id,
            'rating' => 5,
            'comment' => 'Not done yet but great so far.',
        ])->assertUnprocessable()->assertJsonValidationErrors('project_id');
    }

    public function test_public_site_settings_and_pages(): void
    {
        SiteSetting::setMany(['facebook_url' => 'https://facebook.com/valorhub', 'platform_fee_percent' => '7']);

        $settings = $this->getJson('/api/site-settings')->assertOk()->json('data');
        $this->assertSame('https://facebook.com/valorhub', $settings['facebook_url']);
        $this->assertArrayNotHasKey('platform_fee_percent', $settings);

        Page::create(['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'content' => '<p>Hello</p>']);
        Page::create(['slug' => 'draft-page', 'title' => 'Draft', 'content' => 'x', 'is_published' => false]);

        $this->getJson('/api/pages/privacy-policy')->assertOk()->assertJsonPath('data.title', 'Privacy Policy');
        $this->getJson('/api/pages/draft-page')->assertNotFound();
    }
}
