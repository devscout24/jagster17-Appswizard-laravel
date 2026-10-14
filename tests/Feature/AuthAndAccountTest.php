<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthAndAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_login_and_fetch_me(): void
    {
        $this->postJson('/api/auth/register', [
            'name'                  => 'Sarah Johnson',
            'email'                 => 'sarah.j@email.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(201)->assertJsonPath('data.user.role', 'customer');

        $token = $this->postJson('/api/auth/login', ['email' => 'sarah.j@email.com', 'password' => 'secret123'])
            ->assertOk()
            ->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'sarah.j@email.com');
    }

    public function test_login_rejects_bad_credentials_and_admins(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'nobody@test.com', 'password' => 'x'])->assertStatus(401);

        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'api');
        $admin = User::factory()->create(['password' => 'password']);
        $admin->assignRole('super_admin');

        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => 'password'])->assertStatus(403);
    }

    public function test_portal_routes_require_auth_and_matching_role(): void
    {
        $this->getJson('/api/customer/dashboard')->assertStatus(401);
        $this->getJson('/api/contractor/dashboard')->assertStatus(401);
        $this->getJson('/api/quotes')->assertStatus(401);

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer, 'api')->getJson('/api/contractor/dashboard')->assertStatus(403);
    }

    public function test_forgot_password_sends_reset_link_and_reset_works(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@test.com']);
        $user->assignRole('customer');

        $this->postJson('/api/auth/forgot-password', ['email' => 'reset@test.com'])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'unknown@test.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);

        $this->postJson('/api/auth/reset-password', [
            'token'                 => Password::createToken($user),
            'email'                 => 'reset@test.com',
            'password'              => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertOk();

        $this->postJson('/api/auth/login', ['email' => 'reset@test.com', 'password' => 'brandnew123'])->assertOk();
    }

    public function test_account_settings_and_password_change(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);
        $user->assignRole('customer');

        $this->actingAs($user, 'api')->getJson('/api/account/settings')
            ->assertOk()
            ->assertJsonPath('data.notification_preferences.email_notifications', true);

        $this->actingAs($user, 'api')->putJson('/api/account/settings', [
            'phone'                    => '(555) 987-6543',
            'notification_preferences' => ['email_notifications' => false],
        ])->assertOk()->assertJsonPath('data.notification_preferences.email_notifications', false);

        $this->actingAs($user, 'api')->putJson('/api/account/password', [
            'current_password'      => 'wrong',
            'password'              => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertStatus(422);

        $this->actingAs($user, 'api')->putJson('/api/account/password', [
            'current_password'      => 'oldpassword',
            'password'              => 'newpassword1',
            'password_confirmation' => 'newpassword1',
        ])->assertOk();
    }
}
