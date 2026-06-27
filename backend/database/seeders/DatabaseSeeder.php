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
            'name' => 'Northwind Cloud',
            'slug' => 'northwind-cloud',
        ]);

        // 1 admin, 2 agents, 2 customers — all with known password
        $admin = User::create([
            'name' => 'Sarah Chen',
            'email' => 'admin@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'admin',
        ]);

        $agent1 = User::create([
            'name' => 'Marcus Reid',
            'email' => 'agent1@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'agent',
        ]);

        $agent2 = User::create([
            'name' => 'Priya Patel',
            'email' => 'agent2@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'agent',
        ]);

        $customer1 = User::create([
            'name' => 'David Okonkwo',
            'email' => 'customer1@pulsedesk.test',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'customer',
        ]);

        $customer2 = User::create([
            'name' => 'Emma Wilson',
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

        $subjects = [
            'CSV export button missing from ticket list view',
            'Login returns 500 error on password reset',
            'Billing: double-charged after Pro upgrade',
            'Slack integration stopped syncing messages',
            'Mobile view: reply box overlaps with nav bar',
            'API rate limit header returns incorrect value',
            'Cannot assign ticket to agent via API PATCH',
            'Data export fails with 502 timeout on large datasets',
            "Dashboard charts don't load in Safari 17",
            'SSO login with Google returns redirect_uri_mismatch',
            'Email notifications contain broken ticket links',
            'Webhook delivery delayed by 15+ minutes',
        ];

        $descriptions = [
            'The export button that used to be in the top-right of the ticket list is no longer visible after the latest UI update. I\'ve checked in both Chrome and Firefox.',
            'When I click the reset password link in the email, I get a 500 error page. The URL contains a valid token. This started happening two days ago.',
            'I upgraded to Pro on June 20th and was charged twice — $49 appears twice on my credit card statement. I\'d like a refund for the duplicate charge.',
            'Our Slack channel #support-queue stopped receiving new ticket notifications about a week ago. Disconnecting and reconnecting didn\'t help.',
            'On iPhone 14 (Safari), the reply text box partially hides behind the bottom navigation bar. Makes it hard to type longer responses.',
            'The X-RateLimit-Remaining header shows 60 even when I\'ve already made 40 requests in the current window. The limit itself seems enforced correctly though.',
            'Sending PATCH /api/tickets/42 with {"assignee_id": 3} returns 200 but the assignee_id doesn\'t change. Other fields like status update fine.',
            'When exporting 6 months of ticket data (about 12k tickets), the request times out after 30s with a 502. Smaller exports work fine.',
            'The charts on the analytics dashboard show a blank area in Safari 17.0 on macOS Sonoma. Console shows a ResizeObserver error. Works fine in Chrome.',
            'When attempting Google SSO, we get: redirect_uri_mismatch. The configured redirect URI in the admin panel is https://app.northwind.cloud/auth/callback but Google expects https://northwind.cloud/auth/callback.',
            'Email notifications link to https://app.pulsedesk.io/tickets/42 but our custom domain is help.northwind.cloud. Clicking gives a 404.',
            'Our automation relies on ticket.created webhooks but they\'re arriving 15-20 minutes late. This started after the infrastructure migration last Thursday.',
        ];

        // Hand-authored comment bodies per ticket.
        // Distribution matches original: commentCount = (i % 3) + 1
        // Internal/public pattern: isInternal = (j + i) % 3 === 0
        $commentBodies = [
            // Ticket 0: 1 comment — internal (agent1)
            ['Confirmed — the export button was removed in the v2.3 UI refactor. Need to check if this was intentional or a regression.'],
            // Ticket 1: 2 comments — both public (customer2)
            ['Just to add — I\'ve tried in incognito mode too and get the same 500. The initial email arrives fine.',
             'Any update on this? I\'m locked out and need to access reports for a client meeting tomorrow.'],
            // Ticket 2: 3 comments — public(c1), internal(agent1), public(c1)
            ['Thanks for the quick response. Can you confirm when the refund will hit my account?',
             'Checked Stripe — duplicate charge confirmed. Issued refund REF-8821. Customer has been on Pro since March, this was a webhook retry glitch.',
             'Received the refund confirmation from Stripe. Thank you for sorting this out quickly.'],
            // Ticket 3: 1 comment — internal (agent2)
            ['Found the issue — Slack changed their webhook format. Our integration was sending the old payload structure. Fix deployed in PR #1421. Customer confirmed it\'s working.'],
            // Ticket 4: 2 comments — both public (customer1)
            ['Adding a screenshot — the overlap is about 40px. Rotating to landscape fixes it temporarily.',
             'Also tested on Chrome mobile (Android), same issue there.'],
            // Ticket 5: 3 comments — public(c2), internal(agent2), public(c2)
            ['Here\'s an example: after 40 calls the header still says 60 remaining. The 61st call correctly returns 429.',
             'The rate limiter is computing remaining from the wrong base. The header uses the configured limit (60) but the actual limiter uses a per-route limit of 100. Need to align these.',
             'Got it — let me know when the fix ships. Not blocking us but confusing for our monitoring.'],
            // Ticket 6: 1 comment — internal (agent1)
            ['Bug in UpdateTicketRequest — assignee_id was missing from the allowed fields list. Fixed in commit a3f8b2c. Added test case to prevent regression.'],
            // Ticket 7: 2 comments — both public (customer2)
            ['We need the export for compliance reporting. Is there a way to increase the timeout or queue it?',
             'The queued export approach worked perfectly. Received the download link via email. Closing this.'],
            // Ticket 8: 3 comments — public(c1), internal(agent1), public(c1)
            ['Here\'s the console error: TypeError: ResizeObserver loop completed with undelivered notifications',
             'Known Safari 17 issue with ResizeObserver. The chart library needs a polyfill or we add a debounced wrapper. Low priority since it\'s cosmetic.',
             'Understood, it\'s not blocking us. Would appreciate a fix in the next release though.'],
            // Ticket 9: 1 comment — internal (agent2)
            ['The redirect URI in the admin panel doesn\'t match what\'s registered in Google Cloud Console. Need to update either the panel config or the Google OAuth app. Reaching out to their IT admin.'],
            // Ticket 10: 2 comments — both public (customer1)
            ['This is affecting our whole team — everyone clicks from email and lands on a 404.',
             'Fixed! Links now correctly point to help.northwind.cloud. Thank you.'],
            // Ticket 11: 3 comments — public(c2), internal(agent2), public(c2)
            ['Is there a status page for this? It\'s affecting our SLA timers.',
             'Queue worker was under-provisioned after the migration. Scaled from 2 to 8 workers. Backlog cleared, latency back to under 2s.',
             'Confirmed — webhooks are arriving within 1-2 seconds now. Thank you for the quick fix.'],
        ];

        for ($i = 0; $i < 12; $i++) {
            $ticket = Ticket::create([
                'organization_id' => $org->id,
                'requester_id' => $customers[$i % 2],
                'assignee_id' => $i % 3 === 0 ? null : $agents[$i % 2],
                'subject' => $subjects[$i],
                'description' => $descriptions[$i],
                'status' => $statuses[$i % 4],
                'priority' => $priorities[$i % 4],
                'tags' => $tagSets[$i],
            ]);

            $commentCount = ($i % 3) + 1;
            for ($j = 0; $j < $commentCount; $j++) {
                $isInternal = ($j + $i) % 3 === 0;
                Comment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $isInternal ? $agents[$i % 2] : $customers[$i % 2],
                    'body' => $commentBodies[$i][$j],
                    'type' => $isInternal ? 'internal' : 'public',
                ]);
            }
        }
    }
}
