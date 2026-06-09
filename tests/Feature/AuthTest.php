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
        $response = $this->postJson('/api/register', [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'author']);
    }

    public function test_register_requires_email_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name'     => 'Jane',
            'email'    => 'jane@example.com',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/register', [
            'name'                  => 'Jane',
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/login', [
            'email'    => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/login', [
            'email'    => 'john@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_unauthenticated_request_to_profile_fails(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = auth('api')->login($user);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();
    }
}
