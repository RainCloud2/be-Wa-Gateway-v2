<?php

namespace App\Services\Wa;

use App\Jobs\SendWhatsAppMessage;
use App\Models\ChatSession;
use App\Models\Employee;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class OutgoingMessageService
{
    public function __construct(private WhatsAppDriver $driver) {}

    /**
     * Persist an outgoing message and queue it for delivery.
     *
     * @param  array{ticket_id?: ?int, media_type?: ?string, dispatch?: bool}  $options
     */
    public function createMessage(ChatSession $session, string $sender, string $body, array $options = []): Message
    {
        $shouldDispatch = $options['dispatch'] ?? true;
        $session->loadMissing('employee');
        $simulated = (bool) $session->employee?->isSimulated();

        $message = Message::create([
            'session_id' => $session->id,
            'ticket_id' => $options['ticket_id'] ?? $session->ticket_id,
            'sender' => $sender,
            'body' => $body,
            'media_type' => $options['media_type'] ?? null,
            'delivery_status' => $simulated ? Message::DELIVERY_SENT : Message::DELIVERY_PENDING,
        ]);

        if (! $simulated && $shouldDispatch && $message->isOutgoing()) {
            SendWhatsAppMessage::dispatch($message->id);
        }

        return $message;
    }

    /**
     * Send a one-off message to a raw phone number (no session), e.g. the
     * "not registered" notice or the IT team notification.
     */
    public function sendDirect(string $phone, string $text): SendResult
    {
        $phone = Employee::normalizePhone($phone);

        if ($phone === '') {
            return SendResult::failure('invalid_phone', null, false);
        }

        return $this->driver->send($phone, $text);
    }

    /**
     * Attempt to deliver an already-persisted outgoing message. On success the
     * delivery status becomes "sent"; otherwise it is left untouched so the
     * queue can retry.
     */
    public function attempt(Message $message): SendResult
    {
        $message->loadMissing('session.employee');
        $phone = Employee::normalizePhone($message->session?->employee?->phone);

        if ($phone === '') {
            Log::warning('wa.outgoing.missing_phone', ['message_id' => $message->id]);
            $this->markFailed($message);

            return SendResult::failure('missing_phone', null, false);
        }

        $result = $this->driver->send($phone, (string) $message->body);

        if ($result->ok) {
            $message->forceFill(['delivery_status' => Message::DELIVERY_SENT])->save();
        }

        return $result;
    }

    public function markFailed(Message $message): void
    {
        $message->forceFill(['delivery_status' => Message::DELIVERY_FAILED])->save();
    }
}
