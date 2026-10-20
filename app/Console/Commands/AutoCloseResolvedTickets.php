<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Console\Command;

class AutoCloseResolvedTickets extends Command
{
    protected $signature = 'wa:auto-close-resolved';

    protected $description = 'Close RESOLVED tickets that received no reply within the configured window.';

    public function handle(): int
    {
        $hours = Setting::int('auto_close_resolved_hours', 24);
        $threshold = now()->subHours($hours);
        $closed = 0;

        Ticket::query()
            ->real()
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $threshold)
            ->with('session')
            ->chunkById(100, function ($tickets) use (&$closed) {
                foreach ($tickets as $ticket) {
                    $ticket->update([
                        'status' => Ticket::STATUS_CLOSED,
                        'closed_at' => now(),
                    ]);

                    $ticket->session?->update([
                        'mode' => ChatSession::MODE_AI,
                        'ticket_id' => null,
                        'attempt_count' => 0,
                        'awaiting_ticket_confirmation' => false,
                    ]);

                    $closed++;
                }
            });

        $this->info("Auto-closed {$closed} resolved ticket(s).");

        return self::SUCCESS;
    }
}
