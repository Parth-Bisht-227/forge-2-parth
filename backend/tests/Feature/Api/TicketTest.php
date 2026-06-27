<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;
    private User $orgAdmin;
    private User $orgAgent;
    private User $orgCustomer1;
    private User $orgCustomer2;
    private User $orgBAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['name' => 'Org A', 'slug' => 'org-a']);
        $this->orgB = Organization::factory()->create(['name' => 'Org B', 'slug' => 'org-b']);

        $this->orgAdmin = User::factory()->for($this->orgA)->admin()->create();
        $this->orgAgent = User::factory()->for($this->orgA)->agent()->create();
        $this->orgCustomer1 = User::factory()->for($this->orgA)->customer()->create();
        $this->orgCustomer2 = User::factory()->for($this->orgA)->customer()->create();
        $this->orgBAgent = User::factory()->for($this->orgB)->agent()->create();
    }

    // Test 1: Org A user CANNOT see Org B's tickets — list returns empty for Org B tickets, GET returns 404
    public function test_org_a_user_cannot_see_org_b_tickets(): void
    {
        // Create a ticket in Org B
        $orgBTicket = Ticket::factory()->for($this->orgB)->create([
            'requester_id' => User::factory()->for($this->orgB)->customer()->create()->id,
        ]);

        // Org A agent lists tickets — should NOT see Org B's ticket
        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200);
        $json = $response->json('data');
        $this->assertEmpty($json);

        // Org A agent tries to GET Org B's ticket directly — 404
        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson("/api/tickets/{$orgBTicket->id}");

        $response->assertStatus(404);
    }

    // Test 2: Customer cannot access another org's ticket (404) AND cannot access another customer's ticket in own org (404)
    public function test_customer_cannot_access_other_org_or_other_customer_ticket(): void
    {
        // Org B ticket
        $orgBTicket = Ticket::factory()->for($this->orgB)->create([
            'requester_id' => User::factory()->for($this->orgB)->customer()->create()->id,
        ]);

        // Customer 1 tries to access Org B ticket — 404
        $response = $this->actingAs($this->orgCustomer1, 'sanctum')
            ->getJson("/api/tickets/{$orgBTicket->id}");
        $response->assertStatus(404);

        // Customer 2's ticket in Org A
        $customer2Ticket = Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer2->id,
        ]);

        // Customer 1 tries to access Customer 2's ticket — 404
        $response = $this->actingAs($this->orgCustomer1, 'sanctum')
            ->getJson("/api/tickets/{$customer2Ticket->id}");
        $response->assertStatus(404);
    }

    // Test 3: Basic ticket create + list flow
    public function test_authenticated_user_creates_and_lists_own_ticket(): void
    {
        $response = $this->actingAs($this->orgCustomer1, 'sanctum')
            ->postJson('/api/tickets', [
                'subject' => 'Cannot log in',
                'description' => 'Getting a 500 error on login',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('subject', 'Cannot log in')
            ->assertJsonPath('organization_id', $this->orgA->id)
            ->assertJsonPath('requester_id', $this->orgCustomer1->id)
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('priority', 'medium');

        // List tickets — should see the one they created
        $response = $this->actingAs($this->orgCustomer1, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Cannot log in');
    }

    // Test 4a: Customer CANNOT create internal note (403)
    public function test_customer_cannot_create_internal_note(): void
    {
        $ticket = Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
        ]);

        $response = $this->actingAs($this->orgCustomer1, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'This is internal',
                'type' => 'internal',
            ]);

        $response->assertStatus(403);
    }

    // Test 4b: Customer never sees internal comments
    public function test_customer_never_sees_internal_comments(): void
    {
        $ticket = Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
        ]);

        Comment::factory()->for($ticket)->create([
            'user_id' => $this->orgAgent->id,
            'type' => 'public',
            'body' => 'Public comment',
        ]);
        Comment::factory()->for($ticket)->create([
            'user_id' => $this->orgAgent->id,
            'type' => 'internal',
            'body' => 'Secret internal note',
        ]);

        // Customer GETs ticket — should only see public comment
        $response = $this->actingAs($this->orgCustomer1, 'sanctum')
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $comments = $response->json('comments');
        $this->assertCount(1, $comments);
        $this->assertEquals('public', $comments[0]['type']);
    }

    // Test 4c: Agent CAN create internal note
    public function test_agent_can_create_internal_note(): void
    {
        $ticket = Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
        ]);

        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Investigating the issue',
                'type' => 'internal',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('type', 'internal');
    }

    // Test 5: Filter/search — status, priority, assignee_id, q
    public function test_filter_by_status(): void
    {
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'status' => 'open',
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson('/api/tickets?status=open');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'open');
    }

    public function test_filter_by_priority(): void
    {
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'priority' => 'high',
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson('/api/tickets?priority=high');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.priority', 'high');
    }

    public function test_filter_by_assignee(): void
    {
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'assignee_id' => $this->orgAgent->id,
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'assignee_id' => null,
        ]);

        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson("/api/tickets?assignee_id={$this->orgAgent->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.assignee_id', $this->orgAgent->id);
    }

    public function test_search_by_query(): void
    {
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'subject' => 'Login page broken',
            'description' => 'Users cannot authenticate',
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'subject' => 'Payment gateway',
            'description' => 'Stripe integration issue',
        ]);

        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson('/api/tickets?q=login');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', 'Login page broken');
    }

    public function test_filters_combine(): void
    {
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'status' => 'open',
            'priority' => 'high',
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'status' => 'open',
            'priority' => 'low',
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
            'status' => 'resolved',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson('/api/tickets?status=open&priority=high');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'open')
            ->assertJsonPath('data.0.priority', 'high');
    }

    // Customer visibility: only own tickets in list
    public function test_customer_only_sees_own_tickets_in_list(): void
    {
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer2->id,
        ]);

        // Customer 1 lists — sees only their ticket
        $response = $this->actingAs($this->orgCustomer1, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.requester_id', $this->orgCustomer1->id);
    }

    // Agent sees ALL tickets in their org
    public function test_agent_sees_all_tickets_in_org(): void
    {
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer1->id,
        ]);
        Ticket::factory()->for($this->orgA)->create([
            'requester_id' => $this->orgCustomer2->id,
        ]);

        $response = $this->actingAs($this->orgAgent, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
