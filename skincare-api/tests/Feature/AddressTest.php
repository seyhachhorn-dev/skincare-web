<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'province' => 'Central Province',
        'district' => 'Riverside District',
        'commune' => 'Maple Commune',
        'house_no' => '123',
        'type' => 'home',
    ];

    public function test_user_can_create_an_address(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/addresses', $this->payload);

        $response->assertCreated()->assertJsonPath('data.commune', 'Maple Commune');
        $this->assertDatabaseHas('addresses', ['user_id' => $user->id, 'commune' => 'Maple Commune']);
    }

    public function test_setting_a_new_default_address_unsets_the_previous_default(): void
    {
        $user = User::factory()->create();
        $first = Address::factory()->for($user)->create(['is_default' => true]);
        Sanctum::actingAs($user);

        $this->postJson('/api/addresses', [...$this->payload, 'is_default' => true])->assertCreated();

        $this->assertDatabaseHas('addresses', ['id' => $first->id, 'is_default' => false]);
    }

    public function test_user_can_update_their_own_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/addresses/{$address->id}", ['house_no' => '999']);

        $response->assertOk()->assertJsonPath('data.house_no', '999');
    }

    public function test_user_can_delete_their_own_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/addresses/{$address->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_address_requires_required_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/addresses', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['province', 'district', 'commune', 'house_no', 'type']);
    }
}
