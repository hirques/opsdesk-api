<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_always_creates_a_viewer(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Example', 'email' => 'example@example.test',
            'password' => 'correct-battery-horse-staple',
            'password_confirmation' => 'correct-battery-horse-staple',
            'role' => 'admin',
        ])->assertCreated()->assertJsonPath('data.role', 'viewer')->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', ['email' => 'example@example.test', 'role' => 'viewer']);
    }

    public function test_password_confirmation_is_required(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Example', 'email' => 'example@example.test',
            'password' => 'correct-battery-horse-staple',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_login_and_logout_revoke_token(): void
    {
        User::factory()->create(['email' => 'example@example.test', 'password' => 'correct-battery-horse-staple']);
        $login = $this->postJson('/api/v1/login', ['email' => 'example@example.test', 'password' => 'correct-battery-horse-staple'])
            ->assertOk()->assertJsonStructure(['token']);
        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/v1/me')->assertOk();
        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

        // Check that logout revoked the persisted Sanctum token.
        $tokenId = (int) explode('|', $token, 2)[0];
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

        // Feature tests reuse the same Laravel app across simulated HTTP requests.
        // Its request guard caches the authenticated user, unlike fresh production requests.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_invalid_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'example@example.test']);
        $this->postJson('/api/v1/login', ['email' => 'example@example.test', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_private_routes_require_token(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }
}
