<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TokenAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_registration_success_returns_201(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Juan Luna',
            'email' => 'juan@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Registration successful',
            ])
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ])
            ->assertJsonMissing(['password', 'remember_token']);

        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
    }

    public function test_duplicate_registration_returns_422(): void
    {
        User::factory()->create(['email' => 'juan@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Juan Luna',
            'email' => 'juan@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_registration_returns_422(): void
    {
        $this->postJson('/api/register', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_success_returns_200_and_access_token(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Admin12345',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'Admin12345',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Login successful',
                'token_type' => 'Bearer',
            ])
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'access_token',
            ]);
    }

    public function test_invalid_login_returns_401(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Admin12345',
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid email or password',
            ]);
    }

    public function test_profile_with_valid_token_returns_200(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Admin12345',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_profile_without_token_returns_401(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_logout_returns_200(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Admin12345',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Logout successful',
            ]);
    }

    public function test_revoked_token_returns_401(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Admin12345',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        Auth::forgetGuards();

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_session_authenticated_token_logout_returns_409(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/logout')
            ->assertStatus(409)
            ->assertJson([
                'message' => 'A personal access token is required.',
            ]);
    }

    public function test_registration_throttling_returns_429(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/register', [
                'name' => 'Throttle User '.$i,
                'email' => "throttle{$i}@example.com",
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ])->assertCreated();
        }

        $this->postJson('/api/register', [
            'name' => 'Throttle User 3',
            'email' => 'throttle3@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertTooManyRequests();
    }

    public function test_login_throttling_returns_429(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'Admin12345',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_token_auth_does_not_expose_password_or_token_hash(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Exposure User',
            'email' => 'exposure@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated()
            ->assertJsonMissing(['password', 'remember_token']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'exposure@example.com',
            'password' => 'Password123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonMissing(['password', 'remember_token']);

        $token = $loginResponse->json('access_token');

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonMissing(['password', 'remember_token']);
    }
}
