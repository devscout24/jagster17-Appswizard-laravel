<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthAndAccessTest extends TestCase
{
    use ApiTestHelpers, RefreshDatabase;

    public function test_customer_can_register_log_in_and_fetch_profile(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Sarah Mitchell',
            'email' => 'sarah@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms_accepted' => true,
        ])->assertCreated()->assertJsonPath('data.user.role', 'customer');

        $this->assertNotEmpty($register->json('data.token.access_token'));

        $token = $this->postJson('/api/auth/login', ['email' => 'sarah@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->json('data.token.access_token');

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'sarah@example.com');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeCustomer(['email' => 'a@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'nope-nope'])
            ->assertUnauthorized();
    }

    public function test_guest_from_quote_request_can_claim_account_by_registering(): void
    {
        $this->postJson('/api/quotes', [
            'name' => 'Guest Person',
            'email' => 'guest@example.com',
            'description' => 'Kitchen sink is leaking badly',
            'timeline' => 'asap',
            'zip_code' => '78701',
            'city' => 'Austin',
            'state' => 'TX',
        ])->assertCreated();

        $this->postJson('/api/auth/register', [
            'name' => 'Guest Person',
            'email' => 'guest@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms_accepted' => true,
        ])->assertCreated();

        $this->assertSame('active', User::where('email', 'guest@example.com')->value('status'));
    }

    public function test_cannot_register_over_an_existing_account(): void
    {
        $this->makeCustomer(['email' => 'taken@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms_accepted' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = $this->makeCustomer(['password' => 'secret123']);

        $this->actingAs($user, 'api')->postJson('/api/auth/password', [
            'current_password' => 'wrong-one',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertUnprocessable();

        $this->actingAs($user, 'api')->postJson('/api/auth/password', [
            'current_password' => 'secret123',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ])->assertOk();
    }

    public function test_forgot_password_sends_reset_link_to_frontend(): void
    {
        Notification::fake();
        $user = $this->makeCustomer(['email' => 'reset@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'reset@example.com'])->assertOk();
        // Unknown emails get the same response
        $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, config('app.frontend_url') . '/reset-password?token=');
        });
    }

    public function test_portals_require_login_and_the_right_role(): void
    {
        $this->getJson('/api/customer/dashboard')->assertUnauthorized();
        $this->getJson('/api/contractor/dashboard')->assertUnauthorized();
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->getJson('/api/messages')->assertUnauthorized();
        $this->getJson('/api/quotes')->assertUnauthorized();

        $customer = $this->makeCustomer();
        $contractor = $this->makeContractor();

        $this->actingAs($customer, 'api')->getJson('/api/contractor/dashboard')->assertForbidden();
        $this->actingAs($contractor, 'api')->getJson('/api/customer/dashboard')->assertForbidden();
    }

    public function test_quote_requests_are_only_visible_to_their_customer(): void
    {
        $owner = $this->makeCustomer();
        $other = $this->makeCustomer();

        $quoteId = $this->actingAs($owner, 'api')->postJson('/api/quotes', [
            'description' => 'Replace the water heater',
            'timeline' => 'flexible',
            'zip_code' => '78701',
            'city' => 'Austin',
            'state' => 'TX',
        ])->assertCreated()->json('data.id');

        $this->actingAs($other, 'api')->getJson("/api/quotes/{$quoteId}")->assertNotFound();
        $this->actingAs($owner, 'api')->getJson("/api/quotes/{$quoteId}")->assertOk();
    }

    public function test_account_settings_and_notification_preferences(): void
    {
        $user = $this->makeCustomer();

        $this->actingAs($user, 'api')->putJson('/api/account/settings', ['name' => 'New Name', 'phone' => '5551234567'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->actingAs($user, 'api')->putJson('/api/account/notification-preferences', ['messages' => false])
            ->assertOk()
            ->assertJsonPath('data.notifications.messages', false)
            ->assertJsonPath('data.notifications.quotes', true);
    }
}
