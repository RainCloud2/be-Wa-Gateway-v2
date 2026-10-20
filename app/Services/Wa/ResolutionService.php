<?php

namespace App\Services\Wa;

use App\Models\ChatSession;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

/**
 * Handles the phase after a technician marks a ticket RESOLVED: the next
 * employee reply is classified into solved / not_solved.
 */
class ResolutionService
{
    public function __construct(
        private GeminiClient $gemini,
        private OutgoingMessageService $outgoing,
    ) {}

    public function handle(int $sessionId, ?int $messageId = null): void
    {
        $session = ChatSession::with('employee', 'ticket')->find($sessionId);

        if (! $session) {
            return;
        }

        $ticket = $session->ticket;

        if (! $ticket || $ticket->status !== Ticket::STATUS_RESOLVED) {
            return;
        }

        if ($messageId !== null) {
            $latestId = $session->messages()
                ->where('sender', 'employee')
                ->latest('id')
                ->value('id');

            if ((int) $latestId !== $messageId) {
                return;
            }
        }

        $intent = $this->classify($session, $ticket);

        if ($intent === 'solved') {
            $this->outgoing->createMessage(
                $session,
                'ai',
                "Terima kasih! Tiket *{$ticket->code}* kami tutup. Jika ada kendala lain, silakan chat saya lagi ya.",
                ['ticket_id' => $ticket->id],
            );

            $ticket->update([
                'status' => Ticket::STATUS_CLOSED,
                'closed_at' => now(),
            ]);

            $session->update([
                'mode' => ChatSession::MODE_AI,
                'ticket_id' => null,
                'attempt_count' => 0,
                'awaiting_ticket_confirmation' => false,
            ]);

            return;
        }

        $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);

        $this->outgoing->createMessage(
            $session,
            'ai',
            "Baik, mohon maaf. Tiket *{$ticket->code}* kami buka kembali dan tim IT akan menindaklanjuti ya.",
            ['ticket_id' => $ticket->id],
        );
    }

    private function classify(ChatSession $session, Ticket $ticket): string
    {
        $history = ConversationHistory::forSession($session, 10);
        $systemPrompt = (string) Setting::get('system_prompt', '');

        try {
            $result = $this->gemini->generateStructured($systemPrompt, $history, AiSchemas::resolution());

            return ($result['intent'] ?? '') === 'solved' ? 'solved' : 'not_solved';
        } catch (GeminiException $e) {
            Log::warning('wa.resolution.classify_failed', ['ticket_id' => $ticket->id]);

            // Be conservative: keep the ticket open for the technician.
            return 'not_solved';
        }
    }
}
