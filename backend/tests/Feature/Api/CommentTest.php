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
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['created_by' => $user->id]);
        Comment::factory()->for($ticket)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/tickets/{$ticket->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_store_creates_comment_on_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'This is a comment.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('body', 'This is a comment.')
            ->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('comments', [
            'ticket_id' => $ticket->id,
            'body' => 'This is a comment.',
        ]);
    }

    public function test_store_validates_body(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['created_by' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", []);

        $response->assertStatus(422);
    }

    public function test_store_returns_403_for_other_organization_ticket(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->for($otherOrg)->create();
        $ticket = Ticket::factory()->for($otherOrg)->create(['created_by' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/tickets/{$ticket->id}/comments", [
                'body' => 'Intrusion attempt.',
            ]);

        $response->assertStatus(403);
    }

    public function test_destroy_deletes_comment(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        $ticket = Ticket::factory()->for($org)->create(['created_by' => $user->id]);
        $comment = Comment::factory()->for($ticket)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/tickets/{$ticket->id}/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
