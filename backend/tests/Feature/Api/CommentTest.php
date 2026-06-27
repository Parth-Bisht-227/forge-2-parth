<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_comments_for_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->agent()->create();
        $ticket = Ticket::factory()->for($org)->create(['requester_id' => $user->id]);
        Comment::factory()->for($ticket)->create(['user_id' => $user->id, 'type' => 'public']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/tickets/{$ticket->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_store_cre_public_comment(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->agent()->create();
        $ticket = Ticket::factory()->for($org)->create(['requester_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'This is a public reply.',
                'type' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('body', 'This is a public reply.')
            ->assertJsonPath('type', 'public');
    }

    public function test_store_creates_internal_note_as_agent(): void
    {
        $org = Organization::factory()->create();
        $agent = User::factory()->for($org)->agent()->create();
        $ticket = Ticket::factory()->for($org)->create(['requester_id' => User::factory()->for($org)->customer()->create()->id]);

        $response = $this->actingAs($agent, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Internal investigation note.',
                'type' => 'internal',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('type', 'internal');
    }

    public function test_store_validates_body(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['requester_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", []);

        $response->assertStatus(422);
    }

    public function test_store_returns_404_for_other_organization_ticket(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = User::factory()->for($orgA)->create();
        $ticket = Ticket::factory()->for($orgB)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Intrusion attempt.',
            ]);

        $response->assertStatus(404);
    }

    public function test_destroy_deletes_comment(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->agent()->create();
        $ticket = Ticket::factory()->for($org)->create(['requester_id' => $user->id]);
        $comment = Comment::factory()->for($ticket)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/tickets/{$ticket->id}/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
