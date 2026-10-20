<?php

namespace App\Services\Wa;

use App\Jobs\GenerateAiReply;
use App\Jobs\HandleResolvedReply;
use App\Models\ChatSession;
use App\Models\Employee;
use App\Models\Message;
use App\Models\Setting;
use App\Models\Ticket;

/**
 * Entry point for every incoming WhatsApp message (and for the admin
 * simulator). Responsible for dedupe, whitelist, session handling and
 * routing to the AI / human flows.
 */
class IncomingMessageProcessor
{
    public const NON_TEXT_REPLY = 'Saat ini saya hanya bisa memproses pesan teks. Silakan ketikkan kendala Anda ya.';

    public function __construct(
        private AiTurnService $ai,
        private ResolutionService $resolution,
        private OutgoingMessageService $outgoing,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  bool  $synchronous  When true (simulator), AI runs inline.
     */
    public function process(array $payload, bool $synchronous = false): void
    {
        $from = Employee::normalizePhone($payload['from'] ?? null);

        if ($from === '') {
            return;
        }

        $waMessageId = $payload['wa_message_id'] ?? null;

        if ($waMessageId && Message::query()->where('wa_message_id', $waMessageId)->exists()) {
            return; // dupe
        }

        $employee = Employee::query()->where('phone', $from)->first();

        if (! $employee || ! $employee->is_active) {
            // Silently ignore numbers that are not whitelisted or inactive:
            // no session, no AI call and no reply at all.
            return;
        }

        if ($employee->isSimulated() && ! ($payload['simulated'] ?? false)) {
            return;
        }

        $session = $this->resolveSession($employee);
        $type = (string) ($payload['type'] ?? 'text');
        $text = $this->extractText($payload);

        $message = Message::create([
            'session_id' => $session->id,
            'ticket_id' => $session->ticket_id,
            'sender' => Message::SENDER_EMPLOYEE,
            'body' => $text,
            'media_type' => in_array($type, ['image', 'other'], true) ? $type : null,
            'wa_message_id' => $waMessageId,
        ]);

        $session->forceFill(['last_message_at' => now()])->save();

        if ($type === 'other') {
            $this->outgoing->createMessage($session, Message::SENDER_AI, self::NON_TEXT_REPLY);

            return;
        }

        if ($session->isHumanMode()) {
            $this->handleHumanMode($session, $message->id, $synchronous);

            return;
        }

        // AI mode.
        $image = $this->extractImage($payload);

        if ($synchronous) {
            $this->ai->handle($session->id, null, $image);
        } else {
            GenerateAiReply::dispatch($session->id, $message->id, $image)
                ->delay(now()->addSeconds($this->debounceSeconds()));
        }
    }

    private function resolveSession(Employee $employee): ChatSession
    {
        $session = $employee->sessions()->latest('id')->first();

        if (! $session) {
            return $employee->sessions()->create([
                'mode' => ChatSession::MODE_AI,
                'attempt_count' => 0,
                'awaiting_ticket_confirmation' => false,
                'started_at' => now(),
            ]);
        }

        $timeoutHours = Setting::int('session_timeout_hours', 6);

        if ($session->isAiMode() && $session->last_message_at && $session->last_message_at->lt(now()->subHours($timeoutHours))) {
            $session->forceFill([
                'attempt_count' => 0,
                'awaiting_ticket_confirmation' => false,
            ])->save();
        }

        return $session;
    }

    private function handleHumanMode(ChatSession $session, int $messageId, bool $synchronous): void
    {
        $ticket = $session->ticket;

        if ($ticket && $ticket->status === Ticket::STATUS_RESOLVED) {
            if ($synchronous) {
                $this->resolution->handle($session->id, null);
            } else {
                HandleResolvedReply::dispatch($session->id, $messageId);
            }
        }

        // Otherwise just store the message; the dashboard shows it as unread.
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractText(array $payload): string
    {
        $text = $payload['text'] ?? null;

        return is_string($text) ? trim($text) : '';
    }

    /**
     * Random debounce window (seconds) applied before the AI replies. A random
     * wait makes the bot feel less robotic and merges message bursts.
     */
    private function debounceSeconds(): int
    {
        $min = max(0, (int) config('wa.debounce_min_seconds', 3));
        $max = max($min, (int) config('wa.debounce_max_seconds', 6));

        return $min === $max ? $min : random_int($min, $max);
    }

    /**
     * Extract an inline image (base64) from the payload, when the gateway
     * forwarded one. Reserved for the optional multimodal AI feature.
     *
     * @param  array<string, mixed>  $payload
     * @return array{mime: string, data: string}|null
     */
    private function extractImage(array $payload): ?array
    {
        if (($payload['type'] ?? null) !== 'image') {
            return null;
        }

        $data = $payload['image_base64'] ?? null;

        if (! is_string($data) || $data === '') {
            return null;
        }

        return [
            'mime' => is_string($payload['image_mime'] ?? null) ? $payload['image_mime'] : 'image/jpeg',
            'data' => $data,
        ];
    }
}
