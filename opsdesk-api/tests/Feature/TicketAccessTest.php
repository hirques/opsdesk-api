<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketAccessTest extends TestCase
{
    use RefreshDatabase;

    private function ticketFor(User $user): Ticket
    {
        return Ticket::create(['title' => 'Server unavailable', 'created_by' => $user->id]);
    }

    public function test_viewer_can_create_ticket_but_cannot_choose_owner_status_or_assignee(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($viewer);
        $this->postJson('/api/v1/tickets', [
            'title' => 'VPN issue', 'priority' => 'high', 'created_by' => $other->id,
            'assigned_to' => $other->id, 'status' => 'closed',
        ])->assertCreated()->assertJsonPath('data.created_by', $viewer->id)
            ->assertJsonPath('data.status', 'open')->assertJsonPath('data.assigned_to', null);
    }

    public function test_viewer_only_lists_own_tickets(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();
        $owned = $this->ticketFor($viewer);
        $foreign = $this->ticketFor($other);
        Sanctum::actingAs($viewer);
        $this->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $owned->id);
        $this->getJson('/api/v1/tickets/'.$foreign->id)->assertForbidden();
    }

    public function test_viewer_cannot_update_or_delete(): void
    {
        $viewer = User::factory()->create();
        $ticket = $this->ticketFor($viewer);
        Sanctum::actingAs($viewer);
        $this->patchJson('/api/v1/tickets/'.$ticket->id, ['status' => 'closed'])->assertForbidden();
        $this->deleteJson('/api/v1/tickets/'.$ticket->id)->assertForbidden();
    }

    public function test_operator_can_update_but_not_assign_or_delete(): void
    {
        $operator = User::factory()->create();
        $operator->forceFill(['role' => 'operator'])->save();
        $viewer = User::factory()->create();
        $ticket = $this->ticketFor($viewer);
        Sanctum::actingAs($operator);
        $this->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson('/api/v1/tickets/'.$ticket->id, ['status' => 'in_progress'])->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
        $this->patchJson('/api/v1/tickets/'.$ticket->id, ['assigned_to' => $operator->id])->assertForbidden();
        $this->deleteJson('/api/v1/tickets/'.$ticket->id)->assertForbidden();
    }

    public function test_admin_can_assign_and_delete(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $viewer = User::factory()->create();
        $ticket = $this->ticketFor($viewer);
        Sanctum::actingAs($admin);
        $this->patchJson('/api/v1/tickets/'.$ticket->id, ['assigned_to' => $admin->id])->assertOk()
            ->assertJsonPath('data.assigned_to', $admin->id);
        $this->deleteJson('/api/v1/tickets/'.$ticket->id)->assertNoContent();
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_input_validation_rejects_unknown_status(): void
    {
        $operator = User::factory()->create();
        $operator->forceFill(['role' => 'operator'])->save();
        $ticket = $this->ticketFor($operator);
        Sanctum::actingAs($operator);
        $this->patchJson('/api/v1/tickets/'.$ticket->id, ['status' => 'teleported'])
            ->assertUnprocessable()->assertJsonValidationErrors(['status']);
    }
}
