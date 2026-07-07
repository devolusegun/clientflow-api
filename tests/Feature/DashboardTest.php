<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_user_specific_stats(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        // 2 paid, 1 sent, 1 overdue
        Invoice::factory()->paid()->for($user)->for($client)->create(['total_amount' => 1000]);
        Invoice::factory()->paid()->for($user)->for($client)->create(['total_amount' => 500]);
        Invoice::factory()->sent()->for($user)->for($client)->create(['total_amount' => 700]);
        Invoice::factory()->overdue()->for($user)->for($client)->create(['total_amount' => 300]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard');
        
        $response->assertStatus(200)
            ->assertJsonPath('stats.total_invoices', 4)
            ->assertJsonPath('stats.paid_count', 2)
            ->assertJsonPath('stats.sent_count', 1)
            ->assertJsonPath('stats.overdue_count', 1)
            ->assertJsonPath('stats.total_paid', 1500)
            ->assertJsonPath('stats.total_outstanding', 700)
            ->assertJsonPath('stats.total_overdue', 300);
    }

    public function test_dashboard_does_not_leak_other_users_data(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $client = Client::factory()->for($user)->create();
        $otherClient = Client::factory()->for($otherUser)->create();

        Invoice::factory()->paid()->for($user)->for($client)->create(['total_amount' => 1000]);
        Invoice::factory()->paid()->for($otherUser)->for($otherClient)->create(['total_amount' => 9999]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('stats.total_invoices', 1)
            ->assertJsonPath('stats.total_paid', 1000);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }
}