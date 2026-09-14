<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\Conversation;
use App\Models\CustomerProfile;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MessagesAndChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('business', 'api');
        Role::findOrCreate('customer', 'api');
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_complete_chat_and_messaging_flow(): void
    {
        $contractor = User::factory()->create(['name' => 'Apex Construction']);
        $contractor->assignRole('business');

        BusinessProfile::create([
            'user_id'       => $contractor->id,
            'business_name' => 'Apex Construction LLC',
            'city'          => 'Austin',
            'state'         => 'TX',
            'member_since'  => now(),
        ]);

        $customer = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@client.test']);
        $customer->assignRole('customer');

        CustomerProfile::create([
            'user_id' => $customer->id,
            'city'    => 'Austin',
            'state'   => 'TX',
        ]);

        $project = Project::create([
            'business_id'      => $contractor->id,
            'customer_id'      => $customer->id,
            'title'            => 'Bathroom Renovation',
            'progress_percent' => 30,
            'status'           => 'in_progress',
        ]);

        $contractorToken = auth('api')->login($contractor);
        $contractorHeader = ['Authorization' => 'Bearer ' . $contractorToken];

        // 1. Start Conversation from Contractor to Customer
        $startRes = $this->withHeaders($contractorHeader)
            ->postJson('/api/messages/start', [
                'recipient_id' => $customer->id,
                'project_id'   => $project->id,
                'message'      => 'Hello Jane, we are ready to begin the tile installation tomorrow.',
            ]);

        $startRes->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.other_user.name', 'Jane Smith')
            ->assertJsonPath('data.linked_project.title', 'Bathroom Renovation');

        $conversationId = $startRes->json('data.id');

        // 2. Customer logs in and views conversations list (Node 3223-2212)
        $customerToken = auth('api')->login($customer);
        $customerHeader = ['Authorization' => 'Bearer ' . $customerToken];

        $listRes = $this->withHeaders($customerHeader)
            ->getJson('/api/messages');

        $listRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.total_unread', 1)
            ->assertJsonPath('data.items.0.other_user.name', 'Apex Construction LLC')
            ->assertJsonPath('data.items.0.unread_count', 1);

        // 3. Customer views conversation (marks message as read)
        $detailRes = $this->withHeaders($customerHeader)
            ->getJson('/api/messages/' . $conversationId);

        $detailRes->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.messages.0.body', 'Hello Jane, we are ready to begin the tile installation tomorrow.')
            ->assertJsonPath('data.messages.0.is_sender_me', false);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'sender_id'       => $contractor->id,
        ]);

        $unreadCountRes = $this->withHeaders($customerHeader)
            ->getJson('/api/messages/unread-count');

        $unreadCountRes->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);

        // 4. Customer replies
        $replyRes = $this->withHeaders($customerHeader)
            ->postJson('/api/messages/' . $conversationId, [
                'body' => 'Sounds great! What time should we expect your team?',
            ]);

        $replyRes->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.body', 'Sounds great! What time should we expect your team?')
            ->assertJsonPath('data.is_sender_me', true);

        // Assert Notification created for Contractor
        $this->assertDatabaseHas('notifications', [
            'user_id' => $contractor->id,
            'type'    => 'message',
        ]);

        // 5. Contractor checks conversation thread
        $contractorThreadRes = $this->withHeaders($contractorHeader)
            ->getJson('/api/messages/' . $conversationId);

        $contractorThreadRes->assertStatus(200);
        $this->assertCount(2, $contractorThreadRes->json('data.messages'));
    }

    public function test_cannot_send_empty_message(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::create([
            'business_id' => $user->id,
            'customer_id' => $other->id,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/messages/' . $conversation->id, [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }
}
