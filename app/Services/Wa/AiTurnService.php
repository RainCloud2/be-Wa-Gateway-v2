<?php

namespace App\Services\Wa;

use App\Models\ChatSession;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class AiTurnService
{
    public const OFFER_SUFFIX = "\n\nMau saya buatkan tiket untuk tim IT?";

    public const FALLBACK_MESSAGE = 'Maaf, ada gangguan sementara pada sistem saya. Mau saya buatkan tiket agar tim IT bisa membantu Anda langsung?';

    public function __construct(
        private GeminiClient $gemini,
        private TicketService $tickets,
        private OutgoingMessageService $outgoing,
    ) {}

    /**
     * Run a single AI turn for a session.
     *
     * @param  int|null  $messageId  When provided, the turn is skipped if that
     *                               message is no longer the latest employee
     *                               message (debounce guard).
     */
    /**
     * @param  array{mime: string, data: string}|null  $image  Optional inline image for the turn.
     */
    public function handle(int $sessionId, ?int $messageId = null, ?array $image = null): void
    {
        $session = ChatSession::with('employee')->find($sessionId);

        if (! $session || ! $session->isAiMode()) {
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

        $systemPrompt = (string) Setting::get('system_prompt', SystemPrompt::default());
        $history = ConversationHistory::forSession($session);

        try {
            $result = $this->gemini->generateStructured($systemPrompt, $history, AiSchemas::turn(), $image);
        } catch (GeminiException $e) {
            Log::warning('wa.ai.turn_failed', ['session_id' => $session->id]);
            $this->handleGeminiFailure($session);

            return;
        }

        $this->applyTurn($session, $result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function applyTurn(ChatSession $session, array $result): void
    {
        $intent = (string) ($result['user_intent'] ?? 'unclear');
        $reply = trim((string) ($result['reply'] ?? ''));
        $wantsHuman = (bool) ($result['wants_human'] ?? false);
        $category = is_string($result['category'] ?? null) ? $result['category'] : null;
        $maxAttempts = Setting::int('max_attempts', (int) config('wa.max_attempts', 3));

        $employeeText = $this->lastEmployeeText($session);
        $decision = $this->detectTicketDecision($employeeText);
        $explicitTicketRequest = $this->isExplicitTicketRequest($employeeText);

        Log::debug('wa.ai.ticket_decision', [
            'session_id' => $session->id,
            'keyword_decision' => $decision,
            'user_intent' => $intent,
            'awaiting' => (bool) $session->awaiting_ticket_confirmation,
            'explicit_request' => $explicitTicketRequest,
        ]);

        // Ticket branch. The employee's own words take priority over the model's
        // user_intent, and an explicit "buka tiket" / accept_ticket is honoured
        // even when the AI offered a ticket without the flag being set.
        if ($session->awaiting_ticket_confirmation) {
            $accepts = $decision === 'accept' || ($decision === null && $intent === 'accept_ticket');
            $declines = $decision === 'decline' || ($decision === null && $intent === 'decline_ticket');

            if ($accepts) {
                $this->tickets->createFromSession($session, $category);

                return;
            }

            if ($declines) {
                $session->awaiting_ticket_confirmation = false;
                // Let the offer resurface after two further failed attempts.
                $session->attempt_count = max(0, $maxAttempts - 2);
            }
        } elseif ($explicitTicketRequest || $intent === 'accept_ticket') {
            $this->tickets->createFromSession($session, $category);

            return;
        }

        if ($intent === 'solved') {
            if ($reply === '') {
                $reply = 'Syukurlah, senang mendengarnya. Jika ada kendala lain, silakan hubungi saya lagi ya.';
            }

            $this->outgoing->createMessage($session, 'ai', $reply);
            $session->update([
                'attempt_count' => 0,
                'awaiting_ticket_confirmation' => false,
            ]);

            return;
        }

        if ($intent === 'not_solved') {
            $session->attempt_count = (int) $session->attempt_count + 1;
        }

        $shouldOffer = $session->attempt_count >= $maxAttempts
            || $wantsHuman
            || $this->looksLikeTicketOffer($reply);

        if ($shouldOffer) {
            $session->awaiting_ticket_confirmation = true;
        }

        if ($reply === '') {
            $reply = 'Baik, saya bantu ya. Bisa dijelaskan lebih detail kendalanya?';
        }

        if ($shouldOffer && ! str_contains(mb_strtolower($reply), 'tiket')) {
            $reply .= self::OFFER_SUFFIX;
        }

        $session->save();

        $this->outgoing->createMessage($session, 'ai', $reply);
    }

    private function lastEmployeeText(ChatSession $session): string
    {
        $body = $session->messages()
            ->where('sender', 'employee')
            ->latest('id')
            ->value('body');

        return mb_strtolower(trim((string) $body));
    }

    /**
     * True when the employee explicitly asks for a ticket, e.g. "buka tiket".
     */
    private function isExplicitTicketRequest(string $text): bool
    {
        return (bool) preg_match('/\b(buka|buatkan|bikin|minta|ajukan|open)\s+tiket\b/u', $text);
    }

    /**
     * True when the AI reply itself is offering to create a ticket. In that case
     * the awaiting flag must be set so the next "yes" is understood, even though
     * the offer came from the model rather than from the attempt counter.
     */
    private function looksLikeTicketOffer(string $reply): bool
    {
        $lower = mb_strtolower($reply);

        if (! str_contains($lower, 'tiket')) {
            return false;
        }

        return (bool) preg_match('/\b(ingin|mau|perlu|butuh)\b.*\btiket\b/u', $lower)
            || str_contains($lower, 'buatkan tiket')
            || str_contains($lower, 'buka tiket');
    }

    /**
     * Deterministic interpretation of the employee's reply to a ticket offer.
     * Returns "accept", "decline" or null when the wording is ambiguous.
     */
    private function detectTicketDecision(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        // Decline is checked first so that "ya tapi tidak usah" is a decline.
        // Only explicit refusals count, so "tidak bisa" (still broken) is not
        // mistaken for declining the ticket.
        if (preg_match('/\b(tidak\s+usaha?|tidak\s+perlu|tidak\s+dulu|jangan|gak\s+usaha?|ga\s+usaha?|nggak\s+usaha?|ngga\s+usaha?|gausah|batal|skip|nanti\s+(aja|saja|dulu))\b/u', $text)) {
            return 'decline';
        }

        if (preg_match('/\b(ya|iya|yasudah|y|ok|oke|okey|okay|boleh|mau|silakan|silahkan|lanjut|setuju|buatkan|buat|tolong)\b/u', $text)) {
            return 'accept';
        }

        return null;
    }

    private function handleGeminiFailure(ChatSession $session): void
    {
        $session->awaiting_ticket_confirmation = true;
        $session->save();

        $this->outgoing->createMessage($session, 'ai', self::FALLBACK_MESSAGE);
    }
}
