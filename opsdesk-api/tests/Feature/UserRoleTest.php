<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_list_users_and_change_roles(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($viewer);
        $this->getJson('/api/v1/users')->assertForbidden();
        $this->patchJson('/api/v1/users/'.$other->id.'/role', ['role' => 'admin'])->assertForbidden();
    }

    public function test_admin_can_promote_another_user_but_cannot_change_own_role(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $viewer = User::factory()->create();
        Sanctum::actingAs($admin);
        $this->patchJson('/api/v1/users/'.$viewer->id.'/role', ['role' => 'operator'])
            ->assertOk()->assertJsonPath('data.role', 'operator');
        $this->patchJson('/api/v1/users/'.$admin->id.'/role', ['role' => 'viewer'])->assertForbidden();
    }

    public function test_admin_promotion_command_requires_existing_account(): void
    {
        $this->artisan('ops:promote-admin', ['email' => 'missing@example.test'])->assertFailed();
        $user = User::factory()->create(['email' => 'person@example.test']);
        $this->artisan('ops:promote-admin', ['email' => 'person@example.test'])->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
    }
}
