<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_tickets_for_user_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        Ticket::factory()->for($org)->create(['created_by' => $user->id]);
        // Ticket from a different org should NOT appear
        $otherOrg = Organization::factory()->create();
        Ticket::factory()->for($otherOrg)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_store_creates_ticket_in_user_organization(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets', [
                'title' => 'Cannot log in',
                'description' => 'Getting a 500 error on login',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('organization_id', $org->id)
            ->assertJsonPath('created_by', $user->id)
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('priority', 'normal');

        $this->assertDatabaseHas('tickets', [
            'title' => 'Cannot log in',
            'organization_id' => $org->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets', []);

        $response->assertStatus(422);
    }

    public function test_show_returns_ticket_with_relations(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $ticket->id);
    }

    public function test_show_returns_403_for_other_organization_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->for($otherOrg)->create();
        $ticket = Ticket::factory()->for($otherOrg)->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(403);
    }

    public function test_update_modifies_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/tickets/{$ticket->id}", [
                'status' => 'in_progress',
                'priority' => 'high',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'in_progress')
            ->assertJsonPath('priority', 'high');
    }

    public function test_destroy_deletes_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }
}
