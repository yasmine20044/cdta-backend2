<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Rate Limiting Test
 *
 * Verifies that the throttle middleware correctly limits
 * the number of requests to sensitive endpoints.
 *
 * Login routes use throttle:5,1 (5 requests per minute).
 *
 * OWASP Reference: Brute Force Protection
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the login endpoint blocks requests after exceeding the rate limit.
     * The route has throttle:5,1 — max 5 requests per 1 minute.
     */
    public function test_login_rate_limit_blocks_after_5_requests(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        // Send 5 requests (should all be accepted — 401 for wrong password)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'admin@cdta.dz',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th request should be rate-limited (429 Too Many Requests)
        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@cdta.dz',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(429);
    }

    /**
     * Test that the OTP verification endpoint is also rate-limited.
     */
    public function test_otp_verification_rate_limit(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        // Send 5 OTP verification attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/verify-otp', [
                'user_id' => $user->id,
                'otp' => 000000,
            ]);
        }

        // 6th attempt should be rate-limited
        $response = $this->postJson('/api/v1/verify-otp', [
            'user_id' => $user->id,
            'otp' => 000000,
        ]);

        $response->assertStatus(429);
    }
}
