<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function statefulHeaders(): array
    {
        $response = $this->get('/sanctum/csrf-cookie', [
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
        ]);

        return [
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
            'Accept' => 'application/json',
            'X-XSRF-TOKEN' => $response->getCookie('XSRF-TOKEN')?->getValue(),
        ];
    }

    public function test_csrf_cookie_route_is_available(): void
    {
        $response = $this->get('/sanctum/csrf-cookie', [
            'Origin' => 'http://localhost:3000',
        ]);

        $response->assertNoContent();
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $this->statefulHeaders());

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ])
            ->assertJsonMissing(['password', 'remember_token']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));

        $this->getJson('/api/v1/auth/user', [
            'Origin' => 'http://localhost:3000',
        ])->assertOk();
    }

    public function test_duplicate_email_registration_returns_422(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $this->statefulHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validation_failures_return_422(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [], $this->statefulHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $this->statefulHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ]);

        $this->getJson('/api/v1/auth/user', [
            'Origin' => 'http://localhost:3000',
        ])->assertOk();
    }

    public function test_invalid_login_credentials_return_422(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ], $this->statefulHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validation_failures_return_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [], $this->statefulHeaders());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_excessive_login_attempts_return_429(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ], $this->statefulHeaders())->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ], $this->statefulHeaders())->assertTooManyRequests();
    }

    public function test_excessive_registration_attempts_return_429(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/register', [
                'name' => "Test User {$i}",
                'email' => "test{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ], $this->statefulHeaders())->assertCreated();
        }

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $this->statefulHeaders())->assertTooManyRequests();
    }

    public function test_user_can_logout(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $this->statefulHeaders())->assertOk();

        $response = $this->postJson('/api/v1/auth/logout', [], $this->statefulHeaders());

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->getJson('/api/v1/auth/user', [
            'Origin' => 'http://localhost:3000',
        ])->assertUnauthorized();
    }

    public function test_unauthenticated_logout_returns_401(): void
    {
        $this->postJson('/api/v1/auth/logout', [], [
            'Origin' => 'http://localhost:3000',
            'Accept' => 'application/json',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_endpoint_returns_public_fields(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $this->statefulHeaders())->assertOk();

        $response = $this->getJson('/api/v1/auth/user', [
            'Origin' => 'http://localhost:3000',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ])
            ->assertJsonMissing(['password', 'remember_token']);
    }

    public function test_unauthenticated_user_endpoint_returns_401(): void
    {
        $this->getJson('/api/v1/auth/user', [
            'Origin' => 'http://localhost:3000',
        ])->assertUnauthorized();
    }
}
