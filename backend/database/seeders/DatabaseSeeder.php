<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name' => 'Demo Organization',
            'slug' => 'demo-organization',
        ]);

        // 1 admin, 2 agents, 2 customers — all with known password
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'admin',
        ]);

        $agent1 = User::create([
            'name' => 'Agent One',
            'email' => 'agent1@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'agent',
        ]);

        $agent2 = User::create([
            'name' => 'Agent Two',
            'email' => 'agent2@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'agent',
        ]);

        $customer1 = User::create([
            'name' => 'Customer One',
            'email' => 'customer1@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);

        $customer2 = User::create([
            'name' => 'Customer Two',
            'email' => 'customer2@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);

        $agents = [$agent1->id, $agent2->id];
        $customers = [$customer1->id, $customer2->id];
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $tagSets = [['bug'], ['urgent', 'backend'], null, ['frontend'], null, null, ['api'], null, null, ['infra'], null, null];

        // ~12 tickets with a mix of status/priority/assignee, some with tags
        for ($i = 0; $i < 12; $i++) {
            $ticket = Ticket::create([
                'organization_id' => $org->id,
                'requester_id' => $customers[$i % 2],
                'assignee_id' => $i % 3 === 0 ? null : $agents[$i % 2],
                'subject' => fake()->sentence(6),
                'description' => fake()->paragraph(3),
                'status' => $statuses[$i % 4],
                'priority' => $priorities[$i % 4],
                'tags' => $tagSets[$i],
            ]);

            // A handful of comments (both public and internal)
            $commentCount = ($i % 3) + 1;
            for ($j = 0; $j < $commentCount; $j++) {
                $isInternal = ($j + $i) % 3 === 0;
                Comment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $isInternal ? $agents[$i % 2] : $customers[$i % 2],
                    'body' => fake()->paragraph(2),
                    'type' => $isInternal ? 'internal' : 'public',
                ]);
            }
        }
    }
}
