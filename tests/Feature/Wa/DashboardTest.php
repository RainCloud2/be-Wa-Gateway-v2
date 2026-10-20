<?php

namespace Tests\Feature\Wa;

use App\Livewire\Wa\Employees;
use App\Livewire\Wa\Simulator;
use App\Livewire\Wa\TicketDetail;
use App\Models\ChatSession;
use App\Models\Employee;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new PermissionSeeder)->run();
        (new SettingsSeeder)->run();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function ticket(Employee $employee): Ticket
    {
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now(),
        ]);

        return Ticket::create([
            'code' => 'TKT-0001',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Printer error',
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now(),
        ]);
    }

    private function messageAt(ChatSession $session, string $body, Carbon $at): Message
    {
        $message = new Message([
            'session_id' => $session->id,
            'sender' => Message::SENDER_EMPLOYEE,
            'body' => $body,
        ]);
        $message->created_at = $at;
        $message->updated_at = $at;
        $message->save();

        return $message;
    }

    public function test_admin_can_access_all_wa_pages(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $ticket = $this->ticket($employee);

        $this->actingAs($admin)->get('/wa/tickets')->assertOk();
        $this->actingAs($admin)->get("/wa/tickets/{$ticket->id}")->assertOk();
        $this->actingAs($admin)->get('/wa/employees')->assertOk();
        $this->actingAs($admin)->get('/wa/settings')->assertOk();
        $this->actingAs($admin)->get('/wa/whatsapp')->assertOk();
        $this->actingAs($admin)->get('/wa/simulator')->assertOk();
    }

    public function test_technician_can_view_tickets_but_not_settings(): void
    {
        $technician = $this->user('technician');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $this->ticket($employee);

        $this->actingAs($technician)->get('/wa/tickets')->assertOk();
        $this->actingAs($technician)->get('/wa/settings')->assertForbidden();
        $this->actingAs($technician)->get('/wa/simulator')->assertForbidden();
        $this->actingAs($technician)->get('/wa/employees')->assertForbidden();
    }

    public function test_technician_reply_is_sent(): void
    {
        $technician = $this->user('technician');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $ticket = $this->ticket($employee);

        Livewire::actingAs($technician)
            ->test(TicketDetail::class, ['ticket' => $ticket])
            ->set('reply', 'Baik, kami cek ya.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'sender' => Message::SENDER_TECH,
            'body' => 'Baik, kami cek ya.',
            'delivery_status' => Message::DELIVERY_SENT,
        ]);
    }

    public function test_marking_resolved_sends_confirmation_to_employee(): void
    {
        $technician = $this->user('technician');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $ticket = $this->ticket($employee);

        Livewire::actingAs($technician)
            ->test(TicketDetail::class, ['ticket' => $ticket])
            ->call('updateStatus', 'RESOLVED');

        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->assertDatabaseHas('messages', [
            'sender' => Message::SENDER_AI,
            'ticket_id' => $ticket->id,
        ]);
    }

    private function closedTicket(Employee $employee): array
    {
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_AI,
            'started_at' => now(),
        ]);

        $ticket = Ticket::create([
            'code' => 'TKT-0010',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Sudah selesai',
            'status' => Ticket::STATUS_CLOSED,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now()->subDay(),
            'resolved_at' => now()->subHour(),
            'closed_at' => now()->subMinutes(30),
        ]);

        return [$ticket, $session];
    }

    public function test_closed_ticket_cannot_be_set_back_to_resolved(): void
    {
        $technician = $this->user('technician');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        [$ticket] = $this->closedTicket($employee);

        Livewire::actingAs($technician)
            ->test(TicketDetail::class, ['ticket' => $ticket])
            ->call('updateStatus', 'RESOLVED');

        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
    }

    public function test_reopening_a_closed_ticket_clears_timestamps_and_reattaches_the_session(): void
    {
        $technician = $this->user('technician');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        [$ticket, $session] = $this->closedTicket($employee);

        Livewire::actingAs($technician)
            ->test(TicketDetail::class, ['ticket' => $ticket])
            ->call('updateStatus', 'IN_PROGRESS');

        $ticket->refresh();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);

        $session->refresh();
        $this->assertSame(ChatSession::MODE_HUMAN, $session->mode);
        $this->assertSame($ticket->id, $session->ticket_id);
    }

    public function test_ticket_times_are_displayed_in_the_configured_timezone(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $ticket = $this->ticket($employee);
        $ticket->update(['opened_at' => Carbon::parse('2026-10-19 08:00:00', 'UTC')]);

        $this->actingAs($admin)
            ->get("/wa/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSee('19/10/2026 15:00');
    }

    public function test_simulator_processes_message_through_pipeline_with_a_dummy_number(): void
    {
        $admin = $this->user('admin');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'reply' => 'Coba restart printer.',
                    'category' => 'printer',
                    'user_intent' => 'new_info',
                    'wants_human' => false,
                ])]]]]],
            ], 200),
        ]);
        Http::preventStrayRequests();

        Livewire::actingAs($admin)
            ->test(Simulator::class)
            ->set('message', 'printer saya error')
            ->call('send')
            ->assertHasNoErrors();

        $persona = Employee::simulated()->first();
        $this->assertNotNull($persona);
        $this->assertStringStartsWith(Employee::SIMULATION_PHONE_PREFIX, $persona->phone);

        $this->assertDatabaseHas('messages', ['sender' => 'employee', 'body' => 'printer saya error']);
        $this->assertDatabaseHas('messages', ['sender' => 'ai', 'body' => 'Coba restart printer.']);
        $this->assertDatabaseMissing('employees', ['phone' => '628123456789']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'gateway.test'));
    }

    public function test_simulator_reset_deletes_only_simulation_data(): void
    {
        $admin = $this->user('admin');

        $real = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $realTicket = $this->ticket($real);
        $realSession = $realTicket->session;

        $persona = Employee::createSimulation();
        $simSession = ChatSession::create([
            'employee_id' => $persona->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now(),
        ]);
        $simTicket = Ticket::create([
            'code' => 'TKT-0002',
            'employee_id' => $persona->id,
            'session_id' => $simSession->id,
            'title' => 'Printer simulasi',
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now(),
        ]);
        $simSession->update(['ticket_id' => $simTicket->id]);

        Message::create(['session_id' => $simSession->id, 'sender' => 'employee', 'body' => 'halo simulasi']);
        Message::create(['session_id' => $realSession->id, 'sender' => 'employee', 'body' => 'halo asli']);

        Livewire::actingAs($admin)
            ->test(Simulator::class)
            ->call('selectEmployee', (string) $persona->id)
            ->call('resetSession');

        $this->assertDatabaseHas('employees', ['id' => $persona->id, 'is_simulated' => true]);
        $this->assertDatabaseMissing('tickets', ['id' => $simTicket->id]);
        $this->assertDatabaseMissing('chat_sessions', ['id' => $simSession->id]);
        $this->assertDatabaseMissing('messages', ['session_id' => $simSession->id]);

        $this->assertDatabaseHas('tickets', ['id' => $realTicket->id]);
        $this->assertDatabaseHas('chat_sessions', ['id' => $realSession->id]);
        $this->assertDatabaseHas('messages', ['session_id' => $realSession->id]);
    }

    public function test_simulation_phone_prefix_cannot_be_registered_as_a_real_employee(): void
    {
        $admin = $this->user('admin');

        Livewire::actingAs($admin)
            ->test(Employees::class)
            ->set('name', 'Budi')
            ->set('phone', '000000000123')
            ->set('is_active', true)
            ->call('store')
            ->assertHasErrors(['phone']);

        $this->assertDatabaseMissing('employees', ['phone' => '000000000123']);
    }

    public function test_admin_can_toggle_employee_active_status(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);

        Livewire::actingAs($admin)
            ->test(Employees::class)
            ->call('toggleActive', $employee->id);

        $this->assertFalse($employee->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(Employees::class)
            ->call('toggleActive', $employee->id);

        $this->assertTrue($employee->fresh()->is_active);
    }

    public function test_user_without_employee_update_permission_cannot_toggle_active_status(): void
    {
        $user = $this->user('user');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);

        $this->assertFalse($user->can('employee.update'));

        Livewire::actingAs($user)
            ->test(Employees::class)
            ->call('toggleActive', $employee->id);

        $this->assertTrue($employee->fresh()->is_active);
    }

    public function test_dashboard_shows_whatsapp_summary_instead_of_master_data(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $ticket = $this->ticket($employee);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Total Tiket')
            ->assertSee('Tiket Aktif')
            ->assertSee('Whitelist Aktif')
            ->assertSee('Tiket Terbaru')
            ->assertSee($ticket->code)
            ->assertDontSee('Total Pekerja')
            ->assertDontSee('Total Bank');
    }

    public function test_closed_ticket_hides_messages_sent_after_closure(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now()->subMinutes(20),
        ]);
        $ticket = Ticket::create([
            'code' => 'TKT-0001',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Printer error',
            'status' => Ticket::STATUS_CLOSED,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now()->subMinutes(15),
            'closed_at' => now()->subMinutes(10),
        ]);
        $session->update(['ticket_id' => $ticket->id]);

        $inside = $this->messageAt($session, 'pesan dalam tiket', now()->subMinutes(12));
        $outside = $this->messageAt($session, 'pesan setelah tiket ditutup', now()->subMinutes(5));

        $this->actingAs($admin)
            ->get("/wa/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSee('pesan dalam tiket')
            ->assertDontSee('pesan setelah tiket ditutup');

        $this->assertNotNull($inside->fresh()->read_at);
        $this->assertNull($outside->fresh()->read_at);
    }

    public function test_new_ticket_only_shows_messages_since_previous_ticket_closed(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now()->subMinutes(30),
        ]);

        $oldTicket = Ticket::create([
            'code' => 'TKT-0001',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Masalah lama',
            'status' => Ticket::STATUS_CLOSED,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now()->subMinutes(25),
            'closed_at' => now()->subMinutes(20),
        ]);
        $this->messageAt($session, 'chat tiket lama', now()->subMinutes(22));

        $newTicket = Ticket::create([
            'code' => 'TKT-0002',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Masalah baru',
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now()->subMinutes(10),
        ]);
        $session->update(['ticket_id' => $newTicket->id]);

        $this->messageAt($session, 'percakapan setelah tiket lama', now()->subMinutes(15));
        $this->messageAt($session, 'pesan tiket baru', now()->subMinutes(5));

        $this->actingAs($admin)
            ->get("/wa/tickets/{$oldTicket->id}")
            ->assertOk()
            ->assertSee('chat tiket lama')
            ->assertDontSee('percakapan setelah tiket lama')
            ->assertDontSee('pesan tiket baru');

        $this->actingAs($admin)
            ->get("/wa/tickets/{$newTicket->id}")
            ->assertOk()
            ->assertSee('percakapan setelah tiket lama')
            ->assertSee('pesan tiket baru')
            ->assertDontSee('chat tiket lama');
    }

    public function test_deleting_employee_keeps_their_tickets(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
        $ticket = $this->ticket($employee);

        Livewire::actingAs($admin)
            ->test(Employees::class)
            ->call('confirmDelete', $employee->id, $employee->name)
            ->call('deleteConfirmed');

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        $this->assertSame('Budi', $ticket->fresh()->employee?->name);
    }

    public function test_re_adding_a_removed_number_restores_the_record(): void
    {
        $admin = $this->user('admin');
        Employee::create(['phone' => '628123456789', 'name' => 'Budi'])->delete();

        Livewire::actingAs($admin)
            ->test(Employees::class)
            ->set('name', 'Budi Baru')
            ->set('phone', '628123456789')
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertSame(1, Employee::withTrashed()->where('phone', '628123456789')->count());
        $this->assertNull(Employee::where('phone', '628123456789')->first()?->deleted_at);
    }
}
