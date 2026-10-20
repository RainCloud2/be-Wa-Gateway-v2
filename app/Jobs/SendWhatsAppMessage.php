<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Wa\OutgoingMessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $messageId) {}

    public function handle(OutgoingMessageService $messages): void
    {
        $message = Message::find($this->messageId);

        if (! $message || ! $message->isOutgoing() || $message->delivery_status === Message::DELIVERY_SENT) {
            return;
        }

        $message->loadMissing('session.employee');

        if ($message->session?->employee?->isSimulated()) {
            $message->forceFill(['delivery_status' => Message::DELIVERY_SENT])->save();

            return;
        }

        $result = $messages->attempt($message);

        if ($result->ok) {
            return;
        }

        $tries = (int) config('wa.outgoing.tries', 3);

        if ($result->retryable && $this->attempts() < $tries) {
            $backoff = (array) config('wa.outgoing.backoff', [15, 45, 120]);
            $delay = $backoff[$this->attempts() - 1] ?? 120;
            $this->release((int) $delay);

            return;
        }

        $messages->markFailed($message);
    }

    public function failed(?Throwable $e): void
    {
        $message = Message::find($this->messageId);

        if ($message && $message->isOutgoing()) {
            app(OutgoingMessageService::class)->markFailed($message);
        }
    }
}
