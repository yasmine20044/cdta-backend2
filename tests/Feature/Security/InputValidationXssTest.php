<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Input Validation & XSS Prevention Test
 *
 * Verifies that all user input is properly sanitized:
 * - strip_tags() on plain-text fields (title, status, location, category)
 * - HTMLPurifier on rich-text fields (description, content, excerpt)
 * - Laravel validation rules are enforced
 *
 * OWASP Reference: Cross-Site Scripting XSS (A03:2021)
 */
class InputValidationXssTest extends TestCase
{
    use RefreshDatabase;

    private function getAdminToken(): string
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'admin',
        ]);

        return $user->createToken('api-token')->plainTextToken;
    }

    /**
     * Test that <script> tags in event titles are stripped by strip_tags().
     */
    public function test_xss_script_tag_in_event_title_is_stripped(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => '<script>alert("xss")</script>Security Event',
            'description' => 'Clean description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $this->assertStringNotContainsString('<script>', $data['title']);
        $this->assertStringNotContainsString('</script>', $data['title']);
        $this->assertEquals('alert("xss")Security Event', $data['title']);
    }

    /**
     * Test that <script> tags in event description are removed by HTMLPurifier.
     */
    public function test_xss_script_tag_in_event_description_is_sanitized(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Legit Event',
            'description' => '<p>Good content</p><script>document.cookie</script>',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $this->assertStringNotContainsString('<script>', $data['description']);
        $this->assertStringContainsString('Good content', $data['description']);
    }

    /**
     * Test that onerror/onclick XSS payloads in description are sanitized.
     */
    public function test_xss_event_handler_attributes_are_sanitized(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Event Handler Test',
            'description' => '<p onmouseover="alert(1)">Hover me</p><img src=x onerror="alert(1)">',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $this->assertStringNotContainsString('onmouseover', $data['description']);
        $this->assertStringNotContainsString('onerror', $data['description']);
    }

    /**
     * Test that XSS payload in page content is sanitized by HTMLPurifier.
     */
    public function test_xss_in_page_content_is_sanitized(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/pages', [
            'title' => 'Test Page',
            'content' => '<p>Safe content</p><script>steal()</script><iframe src="evil.com"></iframe>',
            'status' => 'published',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $this->assertStringNotContainsString('<script>', $data['content']);
        $this->assertStringNotContainsString('<iframe', $data['content']);
    }

    /**
     * Test that XSS payload in news content is sanitized.
     */
    public function test_xss_in_news_content_is_sanitized(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/news', [
            'title' => 'Test News',
            'content' => '<b>Bold text</b><script>evil()</script>',
            'status' => 'published',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $this->assertStringNotContainsString('<script>', $data['content']);
        $this->assertStringContainsString('Bold text', $data['content']);
    }

    /**
     * Test that validation rejects missing required fields.
     */
    public function test_validation_rejects_missing_required_fields(): void
    {
        $token = $this->getAdminToken();

        // Attempt to create an event without the required 'title' field
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'description' => 'No title provided',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test that validation rejects title exceeding max length.
     */
    public function test_validation_rejects_title_exceeding_max_length(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => str_repeat('A', 256), // max:255
            'description' => 'Description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test that <script> tags injected in location field are stripped.
     */
    public function test_xss_in_location_field_is_stripped(): void
    {
        $token = $this->getAdminToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Location XSS Test',
            'description' => 'Safe desc',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => '<img src=x onerror=alert(1)>Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(201);

        $data = $response->json();
        $this->assertStringNotContainsString('<img', $data['location']);
        $this->assertStringNotContainsString('onerror', $data['location']);
    }
}
