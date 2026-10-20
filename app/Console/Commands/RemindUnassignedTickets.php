<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Ticket;
use App\Services\Wa\OutgoingMessageService;
use Illuminate\Console\Command;

class RemindUnassignedTickets extends Command
{
    protected $signature = 'wa:remind-unassigned';

    protected $description = 'Notify the IT team about OPEN tickets still unassigned after 15 minutes.';

    public function __construct(private OutgoingMessageService $outgoing)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $numbers = Setting::phones('it_notify_numbers');

        if ($numbers === []) {
            $this->info('No IT notification numbers configured.');

            return self::SUCCESS;
        }

        $reminded = 0;

        Ticket::query()
            ->real()
            ->where('status', Ticket::STATUS_OPEN)
            ->whereNull('assignee_id')
            ->whereNull('reminder_sent_at')
            ->where('opened_at', '<=', now()->subMinutes(15))
            ->with('employee')
            ->chunkById(100, function ($tickets) use ($numbers, &$reminded) {
                foreach ($tickets as $ticket) {
                    $text = "Pengingat: tiket *{$ticket->code}* belum ditugaskan ke teknisi.\n"
                        ."Pelapor: {$ticket->employee?->name} ({$ticket->employee?->phone})\n"
                        ."Judul: {$ticket->title}";

                    foreach ($numbers as $number) {
                        $this->outgoing->sendDirect($number, $text);
                    }

                    $ticket->update(['reminder_sent_at' => now()]);
                    $reminded++;
                }
            });

        $this->info("Sent reminders for {$reminded} ticket(s).");

        return self::SUCCESS;
    }
}
