<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'currency'              => 'USD',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'currency'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name'                  => 'Another User',
            'email'                 => 'existing@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'wrongpass@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'wrongpass@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'total_revenue', 'total_outstanding']);
    }

    public function test_unauthenticated_user_cannot_fetch_profile(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }
}
