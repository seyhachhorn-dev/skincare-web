<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test for the exact bug found: with APP_DEBUG=true (the
     * default local/testing value here), Laravel's raw exception renderer
     * dumps "exception", "file", "line", and a full "trace" array — file
     * paths and all — into the JSON body for any error type it doesn't
     * specially handle (everything except auth/validation). Every
     * assertion below must hold regardless of APP_DEBUG.
     */
    private function assertNoLeakedDebugInfo($response): void
    {
        $response->assertJsonMissingPath('trace');
        $response->assertJsonMissingPath('file');
        $response->assertJsonMissingPath('line');
        $response->assertJsonMissingPath('exception');
    }

    public function test_forbidden_response_is_clean(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/products', ['name' => 'x', 'price' => 1, 'image' => 'x.png']);

        $response->assertStatus(403)->assertExactJson(['message' => 'This action is restricted to administrators.']);
        $this->assertNoLeakedDebugInfo($response);
    }

    public function test_model_not_found_response_is_clean_and_does_not_leak_class_name(): void
    {
        $response = $this->getJson('/api/products/999999');

        $response->assertStatus(404)->assertExactJson(['message' => 'Resource not found.']);
        $this->assertNoLeakedDebugInfo($response);
        $response->assertDontSee('App\\Models\\Product', false);
    }

    public function test_unknown_route_response_is_clean(): void
    {
        $response = $this->getJson('/api/totally-fake-route');

        $response->assertStatus(404);
        $this->assertNoLeakedDebugInfo($response);
    }

    public function test_wrong_http_method_response_is_clean(): void
    {
        $response = $this->deleteJson('/api/products');

        $response->assertStatus(405);
        $this->assertNoLeakedDebugInfo($response);
        $response->assertJsonPath('message', fn ($message) => str_contains($message, 'DELETE method is not supported'));
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'nope@example.com', 'password' => 'wrong'])
                ->assertStatus(422);
        }

        $response = $this->postJson('/api/auth/login', ['email' => 'nope@example.com', 'password' => 'wrong']);

        $response->assertStatus(429);
        $this->assertNoLeakedDebugInfo($response);
    }

    public function test_register_is_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/register', [])->assertStatus(422);
        }

        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(429);
    }

    public function test_authentication_and_validation_errors_are_unaffected(): void
    {
        // These already had clean built-in handling before this change —
        // confirm the new catch-all doesn't regress them.
        $this->getJson('/api/auth/me')->assertStatus(401)->assertExactJson(['message' => 'Unauthenticated.']);

        $response = $this->postJson('/api/auth/login', ['email' => 'not-an-email']);
        $response->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }
}
