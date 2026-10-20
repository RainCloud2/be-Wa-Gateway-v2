<?php

namespace Tests\Feature\Wa;

use App\Models\ChatSession;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\Ticket;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new SettingsSeeder)->run();
    }

    private function employee(): Employee
    {
        return Employee::create(['phone' => '628123456789', 'name' => 'Budi']);
    }

    public function test_resolved_tickets_are_auto_closed_and_session_reset(): void
    {
        $employee = $this->employee();
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now(),
        ]);
        $ticket = Ticket::create([
            'code' => 'TKT-0001',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Printer',
            'status' => Ticket::STATUS_RESOLVED,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now()->subDay(),
            'resolved_at' => now()->subHours(30),
        ]);
        $session->update(['ticket_id' => $ticket->id]);

        $this->artisan('wa:auto-close-resolved')->assertSuccessful();

        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $session->refresh();
        $this->assertSame(ChatSession::MODE_AI, $session->mode);
        $this->assertNull($session->ticket_id);
    }

    public function test_unassigned_open_tickets_get_a_single_reminder(): void
    {
        Setting::put('it_notify_numbers', ['628111111111']);

        $employee = $this->employee();
        $session = ChatSession::create([
            'employee_id' => $employee->id,
            'mode' => ChatSession::MODE_HUMAN,
            'started_at' => now(),
        ]);
        $ticket = Ticket::create([
            'code' => 'TKT-0002',
            'employee_id' => $employee->id,
            'session_id' => $session->id,
            'title' => 'Jaringan',
            'status' => Ticket::STATUS_OPEN,
            'priority' => Ticket::PRIORITY_NORMAL,
            'opened_at' => now()->subMinutes(20),
        ]);

        $this->artisan('wa:remind-unassigned')->assertSuccessful();
        $this->assertNotNull($ticket->fresh()->reminder_sent_at);

        $firstReminder = $ticket->fresh()->reminder_sent_at;
        $this->artisan('wa:remind-unassigned')->assertSuccessful();
        $this->assertEquals($firstReminder, $ticket->fresh()->reminder_sent_at);
    }
}
