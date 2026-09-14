<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_notifications_with_unread_count_and_filters(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $token = auth('api')->login($user);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // Create 2 unread, 1 read notification
        Notification::create([
            'user_id' => $user->id,
            'title'   => 'New Quote Received',
            'body'    => 'Contractor ABC sent a quote for $1,500.',
            'type'    => 'quote_received',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Invoice Issued',
            'body'    => 'Contractor ABC issued invoice INV-1002.',
            'type'    => 'invoice_issued',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Welcome to ValorHub',
            'body'    => 'Welcome aboard!',
            'type'    => 'welcome',
            'read_at' => now()->subDay(),
        ]);

        // 1. Get All Notifications
        $res = $this->withHeaders($authHeader)
            ->getJson('/api/notifications');

        $res->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.unread_count', 2);

        $this->assertCount(3, $res->json('data.items'));

        // 2. Filter unread
        $unreadRes = $this->withHeaders($authHeader)
            ->getJson('/api/notifications?filter=unread');

        $unreadRes->assertStatus(200);
        $this->assertCount(2, $unreadRes->json('data.items'));
    }

    public function test_can_mark_notification_as_read_and_mark_all_read(): void
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);
        $token = auth('api')->login($user);
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        $n1 = Notification::create([
            'user_id' => $user->id,
            'title'   => 'Review Replied',
            'body'    => 'Contractor replied to your review.',
            'type'    => 'review_reply',
            'read_at' => null,
        ]);

        $n2 = Notification::create([
            'user_id' => $user->id,
            'title'   => 'Job Started',
            'body'    => 'Project is now in progress.',
            'type'    => 'project_status',
            'read_at' => null,
        ]);

        // 1. Mark single notification as read
        $readRes = $this->withHeaders($authHeader)
            ->postJson("/api/notifications/{$n1->id}/read");

        $readRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($readRes->json('data.read_at'));

        // 2. Mark all as read
        $markAllRes = $this->withHeaders($authHeader)
            ->postJson('/api/notifications/mark-all-read');

        $markAllRes->assertStatus(200)
            ->assertJsonPath('status', true);

        $this->assertEquals(0, Notification::where('user_id', $user->id)->whereNull('read_at')->count());

        // 3. Delete notification
        $deleteRes = $this->withHeaders($authHeader)
            ->deleteJson("/api/notifications/{$n1->id}");

        $deleteRes->assertStatus(200)
            ->assertJsonPath('status', true);

        $this->assertDatabaseMissing('notifications', ['id' => $n1->id]);
    }
}
