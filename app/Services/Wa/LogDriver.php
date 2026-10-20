<?php

namespace App\Services\Wa;

use Illuminate\Support\Facades\Log;

/**
 * Driver that never talks to WhatsApp. Outgoing messages are already
 * persisted in the database by the caller; here we only record a short,
 * content-free log line so the flow can be exercised end-to-end.
 */
class LogDriver implements WhatsAppDriver
{
    public function send(string $to, string $text): SendResult
    {
        Log::channel(config('logging.default'))->info('wa.log_driver.send', [
            'to' => $to,
            'length' => strlen($text),
        ]);

        return SendResult::success(202);
    }

    public function status(): array
    {
        return [
            'status' => 'log',
            'qr' => null,
            'phone' => null,
            'connected' => true,
        ];
    }

    public function logout(): bool
    {
        return true;
    }
}
