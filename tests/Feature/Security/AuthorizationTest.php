<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Authorization / Role-Based Access Control Test
 *
 * Verifies that the RoleMiddleware correctly enforces access control:
 * - admin: full access
 * - editor: can create/update/delete content
 * - user: read-only, cannot access admin or editor routes
 *
 * OWASP Reference: Broken Access Control (A01:2021)
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(string $role): array
    {
        $user = User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $role . '@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => $role,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [$user, $token];
    }

    /**
     * Test that a regular 'user' role CANNOT access admin-only routes.
     */
    public function test_user_role_cannot_access_admin_routes(): void
    {
        [$user, $token] = $this->createUserWithToken('user');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'new@cdta.dz',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that an 'editor' role CANNOT access admin-only routes.
     */
    public function test_editor_role_cannot_access_admin_routes(): void
    {
        [$user, $token] = $this->createUserWithToken('editor');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'new@cdta.dz',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that an 'admin' role CAN access admin-only routes.
     */
    public function test_admin_role_can_access_admin_routes(): void
    {
        [$user, $token] = $this->createUserWithToken('admin');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'newuser@cdta.dz',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test that an 'editor' role CAN create events (editor-allowed route).
     */
    public function test_editor_can_create_events(): void
    {
        [$user, $token] = $this->createUserWithToken('editor');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Security Conference',
            'description' => 'Annual security event',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(201);
    }

    /**
     * Test that a regular 'user' role CANNOT create events.
     */
    public function test_user_role_cannot_create_events(): void
    {
        [$user, $token] = $this->createUserWithToken('user');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/events', [
            'title' => 'Unauthorized Event',
            'description' => 'Should be blocked',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that a regular 'user' role CANNOT delete pages.
     */
    public function test_user_role_cannot_delete_pages(): void
    {
        [$user, $token] = $this->createUserWithToken('user');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/pages/1');

        $response->assertStatus(403);
    }

    /**
     * Test that login rejects users with 'user' role
     * (only admin/editor can access the dashboard).
     */
    public function test_user_role_cannot_login_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => 'user',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'regular@cdta.dz',
            'password' => 'SecurePass123',
        ]);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized. Only admins and editors can access the dashboard.']);
    }
}
