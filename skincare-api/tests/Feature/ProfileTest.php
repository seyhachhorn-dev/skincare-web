<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_name_and_email(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile', ['name' => 'New Name', 'email' => 'new@example.com']);

        $response->assertOk()->assertJsonPath('data.name', 'New Name')->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile', [
            // ->create() (not ->image()) so this doesn't require the GD extension —
            // Laravel's "image" validation rule checks MIME type, not pixel data.
            'avatar' => UploadedFile::fake()->create('avatar.jpg', 10, 'image/jpeg'),
        ]);

        $response->assertOk();
        Storage::disk('public')->assertExists($response->json('data.avatar'));
    }

    public function test_user_cannot_take_another_users_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/profile', ['email' => 'taken@example.com']);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
