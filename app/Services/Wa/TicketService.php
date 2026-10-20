<?php

namespace App\Services\Wa;

use App\Models\ChatSession;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketService
{
    public function __construct(
        private GeminiClient $gemini,
        private OutgoingMessageService $outgoing,
    ) {}

    /**
     * Create a ticket from an AI session: generate metadata with Gemini,
     * switch the session to human mode, and notify both parties.
     */
    public function createFromSession(ChatSession $session, ?string $category = null): Ticket
    {
        $session->loadMissing('employee');

        $meta = $this->generateMetadata($session, $category);

        $ticket = DB::transaction(function () use ($session, $meta) {
            $ticket = Ticket::create([
                'code' => Ticket::nextCode(),
                'employee_id' => $session->employee_id,
                'session_id' => $session->id,
                'category' => $meta['category'],
                'title' => $meta['title'],
                'summary' => $meta['summary'],
                'status' => Ticket::STATUS_OPEN,
                'priority' => $meta['priority'],
                'opened_at' => now(),
            ]);

            $session->update([
                'mode' => ChatSession::MODE_HUMAN,
                'ticket_id' => $ticket->id,
                'awaiting_ticket_confirmation' => false,
            ]);

            return $ticket;
        });

        $this->outgoing->createMessage(
            $session,
            'ai',
            $this->ticketCreatedMessage($ticket),
            ['ticket_id' => $ticket->id],
        );

        $this->notifyTechnicians($ticket);

        return $ticket;
    }

    /**
     * @return array{title: string, summary: string, category: string, priority: string}
     */
    private function generateMetadata(ChatSession $session, ?string $category): array
    {
        $history = ConversationHistory::forSession($session, 30);
        $systemPrompt = (string) Setting::get('system_prompt', '');

        try {
            $meta = $this->gemini->generateStructured($systemPrompt, $history, AiSchemas::ticket());

            return [
                'title' => Str::limit(trim((string) ($meta['title'] ?? '')), 120, ''),
                'summary' => trim((string) ($meta['summary'] ?? '')),
                'category' => $this->safeCategory($meta['category'] ?? null, $category),
                'priority' => in_array($meta['priority'] ?? null, Ticket::priorities(), true)
                    ? $meta['priority']
                    : Ticket::PRIORITY_NORMAL,
            ];
        } catch (GeminiException $e) {
            Log::warning('wa.ticket.metadata_fallback', ['session_id' => $session->id]);

            return [
                'title' => $this->fallbackTitle($session),
                'summary' => 'Ringkasan otomatis tidak tersedia. Silakan tinjau riwayat percakapan pada tiket ini.',
                'category' => $this->safeCategory(null, $category),
                'priority' => Ticket::PRIORITY_NORMAL,
            ];
        }
    }

    private function fallbackTitle(ChatSession $session): string
    {
        $firstMessage = $session->messages()
            ->where('sender', 'employee')
            ->orderBy('id')
            ->value('body');

        $title = Str::limit(trim((string) $firstMessage), 80, '');

        return $title !== '' ? $title : 'Kendala IT dari karyawan';
    }

    private function safeCategory(?string $category, ?string $preferred): string
    {
        if ($preferred && in_array($preferred, Ticket::CATEGORIES, true)) {
            return $preferred;
        }

        return in_array($category, Ticket::CATEGORIES, true) ? $category : 'other';
    }

    private function ticketCreatedMessage(Ticket $ticket): string
    {
        $message = "Baik, tiket *{$ticket->code}* sudah saya buatkan.\n"
            .'Tim IT akan segera membalas percakapan ini. Mohon menunggu ya.';

        if (! $this->withinBusinessHours()) {
            $message .= "\n\nCatatan: saat ini di luar jam kerja, jadi balasan bisa sedikit lebih lama.";
        }

        return $message;
    }

    private function notifyTechnicians(Ticket $ticket): void
    {
        $ticket->loadMissing('employee');

        if ($ticket->employee?->isSimulated()) {
            return;
        }

        $numbers = Setting::phones('it_notify_numbers');

        if ($numbers === []) {
            return;
        }

        $link = rtrim((string) config('app.url'), '/').'/wa/tickets/'.$ticket->id;

        $text = "Tiket baru *{$ticket->code}* ({$ticket->priority})\n"
            ."Pelapor: {$ticket->employee?->name} ({$ticket->employee?->phone})\n"
            ."Judul: {$ticket->title}\n"
            ."Kategori: {$ticket->category}\n"
            ."Dashboard: {$link}";

        foreach ($numbers as $number) {
            $this->outgoing->sendDirect($number, $text);
        }
    }

    public function withinBusinessHours(): bool
    {
        $hours = Setting::get('business_hours');

        if (! is_array($hours) || empty($hours['start']) || empty($hours['end'])) {
            return true;
        }

        try {
            $now = Carbon::now($hours['timezone'] ?? config('app.timezone'));
        } catch (\Exception $e) {
            $now = Carbon::now();
        }

        $days = array_map('intval', (array) ($hours['days'] ?? [1, 2, 3, 4, 5]));

        if (! in_array($now->dayOfWeekIso, $days, true)) {
            return false;
        }

        $start = Carbon::createFromFormat('H:i', $hours['start'], $now->timezone);
        $end = Carbon::createFromFormat('H:i', $hours['end'], $now->timezone);

        return $now->between($start, $end);
    }
}
