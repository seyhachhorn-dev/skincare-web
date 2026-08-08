<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    public function test_newly_registered_user_defaults_to_user_role(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'user']);
    }

    public function test_registration_request_cannot_set_its_own_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'user']);
    }

    public function test_admin_can_list_users(): void
    {
        Sanctum::actingAs($this->admin());
        User::factory()->count(2)->create();

        $response = $this->getJson('/api/users');

        // The 2 created here + the admin itself.
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_regular_user_cannot_list_users(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_promote_a_user_to_admin(): void
    {
        Sanctum::actingAs($this->admin());
        $user = User::factory()->create();

        $response = $this->patchJson("/api/users/{$user->id}/role", ['role' => 'admin']);

        $response->assertOk()->assertJsonPath('data.role', 'admin');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
    }

    public function test_admin_can_demote_an_admin_to_user(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/users/{$otherAdmin->id}/role", ['role' => 'user']);

        $response->assertOk()->assertJsonPath('data.role', 'user');
    }

    public function test_regular_user_cannot_assign_roles(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $target = User::factory()->create();

        $response = $this->patchJson("/api/users/{$target->id}/role", ['role' => 'admin']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'user']);
    }

    public function test_role_must_be_admin_or_user(): void
    {
        Sanctum::actingAs($this->admin());
        $target = User::factory()->create();

        $response = $this->patchJson("/api/users/{$target->id}/role", ['role' => 'superadmin']);

        $response->assertStatus(422)->assertJsonValidationErrors('role');
    }

    public function test_guest_cannot_assign_roles(): void
    {
        $target = User::factory()->create();

        $response = $this->patchJson("/api/users/{$target->id}/role", ['role' => 'admin']);

        $response->assertStatus(401);
    }
}
