<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Security Headers Test
 *
 * Verifies that the SecureHeaders middleware adds all required
 * HTTP security headers to every API response.
 *
 * OWASP Reference: Security Misconfiguration (A05:2021)
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that X-Frame-Options header is set to SAMEORIGIN
     * to prevent clickjacking attacks.
     */
    public function test_response_has_x_frame_options_header(): void
    {
        $response = $this->getJson('/api/test');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Test that X-XSS-Protection header is enabled with block mode
     * for browsers that support it.
     */
    public function test_response_has_x_xss_protection_header(): void
    {
        $response = $this->getJson('/api/test');

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    /**
     * Test that X-Content-Type-Options is set to nosniff
     * to prevent MIME-type sniffing attacks.
     */
    public function test_response_has_x_content_type_options_header(): void
    {
        $response = $this->getJson('/api/test');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Test that Referrer-Policy header is set
     * to control referrer information leakage.
     */
    public function test_response_has_referrer_policy_header(): void
    {
        $response = $this->getJson('/api/test');

        $response->assertHeader('Referrer-Policy', 'no-referrer-when-downgrade');
    }

    /**
     * Test that Content-Security-Policy header is present
     * to prevent XSS and data injection attacks.
     */
    public function test_response_has_content_security_policy_header(): void
    {
        $response = $this->getJson('/api/test');

        $response->assertHeader('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
    }
}
