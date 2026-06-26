<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_clients(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Client::factory()->count(3)->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/clients');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'company']],
            ]);
    }

    public function test_user_can_create_client(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/clients', [
                'name'    => 'DonateWater',
                'email'   => 'projects@donatewater.org',
                'company' => 'DonateWater Foundation',
                'country' => 'Switzerland',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'name'    => 'DonateWater',
        ]);
    }

    public function test_user_cannot_view_another_users_client(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        /** @var User $intruder */
        $intruder = User::factory()->create();
        $client = Client::factory()->for($owner)->create();

        $response = $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/clients/{$client->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_client(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/clients/{$client->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('clients', [
            'id'   => $client->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_clients(): void
    {
        $response = $this->getJson('/api/clients');
        $response->assertStatus(401);
    }
}