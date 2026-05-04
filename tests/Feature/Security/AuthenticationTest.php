<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

/**
 * Authentication Security Test
 *
 * Verifies that authentication mechanisms (login, OTP, tokens, logout)
 * function correctly and reject unauthorized access.
 *
 * OWASP Reference: Broken Authentication (A07:2021)
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated requests to protected routes return 401.
     */
    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'title' => 'Test Event',
            'description' => 'Description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test that login with invalid credentials is rejected.
     */
    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@cdta.dz',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);
    }

    /**
     * Test that login with non-existent email is rejected.
     */
    public function test_login_with_nonexistent_email_returns_401(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@cdta.dz',
            'password' => 'SomePassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);
    }

    /**
     * Test that OTP verification with an invalid code is rejected.
     */
    public function test_otp_verification_with_wrong_code_returns_401(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        // Place a known OTP in cache
        Cache::put('otp_' . $user->id, 123456, now()->addMinutes(10));

        $response = $this->postJson('/api/v1/verify-otp', [
            'user_id' => $user->id,
            'otp' => 999999, // wrong OTP
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid or expired OTP']);
    }

    /**
     * Test that OTP verification with the correct code returns a token.
     */
    public function test_otp_verification_with_correct_code_returns_token(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        // Place a known OTP in cache
        Cache::put('otp_' . $user->id, 123456, now()->addMinutes(10));

        $response = $this->postJson('/api/v1/verify-otp', [
            'user_id' => $user->id,
            'otp' => 123456,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'expires_at']);
    }

    /**
     * Test that logout invalidates the current token.
     */
    public function test_logout_invalidates_token(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        // Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/logout');

        $logoutResponse->assertStatus(200)
                       ->assertJson(['message' => 'Logout successful']);

        // Reset the auth guard so the next request doesn't reuse cached auth
        $this->app['auth']->forgetGuards();

        // Try using the same token after logout — should be rejected
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Test',
            'description' => 'Desc',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(401);
    }
}
