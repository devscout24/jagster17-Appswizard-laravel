<?php

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_faqs_with_default_data(): void
    {
        $response = $this->getJson('/api/faqs');

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
                    'filter_tabs' => [
                        '*' => [
                            'key',
                            'label',
                        ],
                    ],
                    'active_tab',
                    'items' => [
                        '*' => [
                            'id',
                            'category',
                            'category_label',
                            'question',
                            'answer',
                            'sort_order',
                        ],
                    ],
                    'breadcrumbs',
                ],
                'code',
            ]);

        $this->assertEquals('Frequently asked questions', $response->json('data.hero.headline'));
        $this->assertEquals(4, count($response->json('data.filter_tabs')));
        $this->assertGreaterThanOrEqual(6, count($response->json('data.items')));
    }

    public function test_can_filter_faqs_by_category(): void
    {
        Faq::create([
            'category' => 'contractors',
            'question' => 'How do I withdraw earnings?',
            'answer' => 'Payouts are transferred directly to your verified bank account.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Faq::create([
            'category' => 'customers',
            'question' => 'How do I cancel a quote?',
            'answer' => 'You can cancel a pending quote request from your customer dashboard.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/faqs?category=contractors');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.active_tab', 'contractors')
            ->assertJsonPath('data.items.0.category', 'contractors')
            ->assertJsonPath('data.items.0.category_label', 'For Contractors');
    }

    public function test_can_search_faqs_by_keyword(): void
    {
        Faq::create([
            'category' => 'payments',
            'question' => 'What credit cards are accepted?',
            'answer' => 'We accept Visa, MasterCard, American Express, and Discover.',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/faqs?search=MasterCard');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.items.0.question', 'What credit cards are accepted?');
    }
}
