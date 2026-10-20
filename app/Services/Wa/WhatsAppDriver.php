<?php

namespace App\Services\Wa;

interface WhatsAppDriver
{
    /**
     * Send a text message to a phone number (bare digits, 628xxx).
     */
    public function send(string $to, string $text): SendResult;

    /**
     * Latest connection status as reported by the gateway.
     *
     * @return array{status: string, qr: ?string, phone: ?string, connected: bool}
     */
    public function status(): array;

    /**
     * Log out the current WhatsApp session (used to switch numbers).
     */
    public function logout(): bool;
}
