<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * File Upload Security Test
 *
 * Verifies that file upload endpoints correctly:
 * - Reject non-image files (PHP scripts, executables, etc.)
 * - Reject files exceeding the 2MB size limit
 * - Accept only allowed MIME types (jpg, jpeg, png)
 * - Handle double-extension attacks safely
 *
 * OWASP Reference: Unrestricted File Upload
 */
class FileUploadSecurityTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test that uploading a PHP file is rejected.
     */
    public function test_uploading_php_file_is_rejected(): void
    {
        $token = $this->getAdminToken();

        $file = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'PHP Upload Test',
            'description' => 'Test description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
            'image' => $file,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    /**
     * Test that uploading an executable file is rejected.
     */
    public function test_uploading_executable_file_is_rejected(): void
    {
        $token = $this->getAdminToken();

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-executable');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'EXE Upload Test',
            'description' => 'Test description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
            'image' => $file,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    /**
     * Test that uploading a file exceeding 2MB is rejected.
     */
    public function test_uploading_oversized_file_is_rejected(): void
    {
        $token = $this->getAdminToken();

        // Create a 3MB image (exceeds 2048KB limit)
        $file = UploadedFile::fake()->image('large.jpg')->size(3000);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Large File Test',
            'description' => 'Test description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
            'image' => $file,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['image']);
    }

    /**
     * Test that uploading a valid JPEG image is accepted.
     */
    public function test_uploading_valid_jpeg_image_is_accepted(): void
    {
        $token = $this->getAdminToken();

        $file = UploadedFile::fake()->image('photo.jpg', 640, 480)->size(500);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Valid Image Test',
            'description' => 'Test description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
            'image' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('image'));
    }

    /**
     * Test that double-extension file (shell.php.jpg) is handled safely.
     * Even if accepted as image, the stored filename uses UUID (not original name).
     */
    public function test_double_extension_file_gets_uuid_filename(): void
    {
        $token = $this->getAdminToken();

        // Fake an image with a suspicious original name
        $file = UploadedFile::fake()->image('shell.php.jpg', 640, 480)->size(500);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Double Extension Test',
            'description' => 'Test description',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
            'image' => $file,
        ]);

        $response->assertStatus(201);

        $storedPath = $response->json('image');
        // The stored filename should NOT contain 'shell' or '.php'
        $this->assertStringNotContainsString('shell', $storedPath);
        $this->assertStringNotContainsString('.php', $storedPath);
    }
}
