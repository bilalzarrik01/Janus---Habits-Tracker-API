<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HabitsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Bilal',
            'email' => 'bilal@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'bilal@example.com',
        ]);
    }

    public function test_login_returns_401_with_invalid_credentials(): void
    {
        User::query()->create([
            'name' => 'Bilal',
            'email' => 'bilal@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bilal@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_authenticated_user_can_create_habit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/habits', [
            'title' => 'Morning Run',
            'description' => 'Run every morning',
            'frequency' => 'daily',
            'target_days' => 20,
            'color' => '#22CC88',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Morning Run');

        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'title' => 'Morning Run',
            'frequency' => 'daily',
        ]);
    }

    public function test_user_cannot_log_same_habit_twice_in_same_day(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $habit = Habit::query()->create([
            'user_id' => $user->id,
            'title' => 'Read 30 min',
            'description' => null,
            'frequency' => 'daily',
            'target_days' => 30,
            'color' => null,
            'is_active' => true,
        ]);

        $payload = [
            'logged_date' => now()->toDateString(),
            'note' => 'Done',
        ];

        $this->postJson("/api/habits/{$habit->id}/logs", $payload)->assertStatus(201);

        $response = $this->postJson("/api/habits/{$habit->id}/logs", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
