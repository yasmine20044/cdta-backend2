<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Insecure Direct Object Reference (IDOR) Test
 *
 * Verifies that users cannot access or manipulate resources
 * they are not authorized to access by guessing/modifying IDs.
 *
 * OWASP Reference: Broken Access Control (A01:2021)
 */
class IdorTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(string $role, string $emailPrefix = ''): array
    {
        $prefix = $emailPrefix ?: $role;
        $user = User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $prefix . '@cdta.dz',
            'password' => Hash::make('SecurePass123'),
            'role' => $role,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return [$user, $token];
    }

    /**
     * Test that accessing a non-existent resource ID returns 404.
     * The response must NOT leak information about other resources.
     */
    public function test_accessing_nonexistent_resource_returns_404(): void
    {
        $response = $this->getJson('/api/v1/events/99999');

        $response->assertStatus(404);
    }

    /**
     * Test that a 'user' role cannot delete events (IDOR via role bypass).
     */
    public function test_user_role_cannot_delete_event_by_id(): void
    {
        // Disable throttle to avoid rate-limit interference between admin and user requests
        $this->withoutMiddleware(ThrottleRequests::class);

        [$admin, $adminToken] = $this->createUserWithToken('admin');

        // Admin creates an event
        $eventResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/v1/events', [
            'title' => 'Admin Event',
            'description' => 'Created by admin',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $eventResponse->assertStatus(201);
        $eventId = $eventResponse->json('id');

        // Regular user tries to delete the admin's event
        [$user, $userToken] = $this->createUserWithToken('user', 'regularuser');

        // Reset auth guard so the new token is used
        $this->app['auth']->forgetGuards();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
        ])->deleteJson('/api/v1/events/' . $eventId);

        $response->assertStatus(403);
    }

    /**
     * Test that a 'user' role cannot update events by ID.
     */
    public function test_user_role_cannot_update_event_by_id(): void
    {
        // Disable throttle to avoid rate-limit interference
        $this->withoutMiddleware(ThrottleRequests::class);

        [$admin, $adminToken] = $this->createUserWithToken('admin');

        // Admin creates an event
        $eventResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->postJson('/api/v1/events', [
            'title' => 'Admin Event',
            'description' => 'Created by admin',
            'status' => 'published',
            'start_date' => '2026-06-01',
            'location' => 'Algiers',
            'category' => 'Conference',
        ]);

        $eventResponse->assertStatus(201);
        $eventId = $eventResponse->json('id');

        // Regular user tries to update the admin's event
        [$user, $userToken] = $this->createUserWithToken('user', 'regularuser');

        // Reset auth guard so the new token is used
        $this->app['auth']->forgetGuards();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
        ])->putJson('/api/v1/events/' . $eventId, [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }
}
