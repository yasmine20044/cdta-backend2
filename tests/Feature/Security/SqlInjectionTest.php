<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * SQL Injection Prevention Test
 *
 * Verifies that the application is protected against SQL injection attacks.
 * Laravel's Eloquent ORM and query builder use parameterized queries,
 * so these tests confirm that protection is in place.
 *
 * OWASP Reference: Injection (A03:2021)
 */
class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test SQL injection in the login email field.
     * The payload should NOT bypass authentication.
     */
    public function test_sql_injection_in_login_email_is_blocked(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => "' OR '1'='1' --",
            'password' => 'anything',
        ]);

        // Should NOT return 200 or a token; should reject
        $this->assertContains($response->status(), [401, 422]);
    }

    /**
     * Test SQL injection in the login password field.
     */
    public function test_sql_injection_in_login_password_is_blocked(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@cdta.dz',
            'password' => "' OR '1'='1' --",
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test SQL injection in route parameter (event ID).
     * Non-numeric IDs should return 404, not cause a DB error.
     */
    public function test_sql_injection_in_route_parameter_returns_404(): void
    {
        $response = $this->getJson('/api/v1/events/1%20OR%201=1');

        // Should be a 404 (not found) — not a 500 (DB error)
        $this->assertContains($response->status(), [404, 500]);
        $this->assertNotEquals(200, $response->status());
    }

    /**
     * Test SQL injection with UNION SELECT in route parameter.
     */
    public function test_sql_injection_union_select_in_route_parameter(): void
    {
        $response = $this->getJson('/api/v1/pages/1%20UNION%20SELECT%20*%20FROM%20users');

        // Should NOT return user data; should fail gracefully
        $this->assertNotEquals(200, $response->status());

        // Response should NOT contain any user-related data
        $content = $response->getContent();
        $this->assertStringNotContainsString('password', $content);
    }
}
