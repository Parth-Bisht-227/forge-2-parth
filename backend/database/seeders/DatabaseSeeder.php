<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name' => 'Demo Organization',
            'slug' => 'demo-organization',
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@pulsedesk.test',
            'organization_id' => $org->id,
            'role' => 'admin',
        ]);
    }
}
