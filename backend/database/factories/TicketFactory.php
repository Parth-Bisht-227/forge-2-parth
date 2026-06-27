<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        return [
            'organization_id' => Organization::factory(),
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'status' => fake()->randomElement($statuses),
            'priority' => fake()->randomElement($priorities),
            'tags' => null,
        ];
    }
}
