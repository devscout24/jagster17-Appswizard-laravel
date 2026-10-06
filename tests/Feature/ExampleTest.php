<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // The backend root sends visitors to the admin panel, which redirects guests to its login
        $response = $this->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }
}
