<?php

namespace App\Services\Wa;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends messages through the wa-gateway service (Node.js + Baileys).
 *
 * Contract (see wagateway-v2):
 *  - POST /send   {to, text} -> 202 queued | 422 validation | 503 wa_disconnected
 *  - GET  /status -> {wa_status, phone, qr, connected, queue_size, uptime}
 *  - POST /logout -> {status: logged_out}
 */
class GatewayDriver implements WhatsAppDriver
{
    public function send(string $to, string $text): SendResult
    {
        try {
            $response = $this->client()->post($this->baseUrl().'/send', [
                'to' => $to,
                'text' => $text,
            ]);
        } catch (ConnectionException $e) {
            return SendResult::failure('connection_error', null, true);
        }

        if ($response->successful()) {
            return SendResult::success($response->status());
        }

        $error = $response->json('error') ?? 'gateway_error';
        // 503 (disconnected) and 5xx are worth retrying; 4xx are not.
        $retryable = $response->status() >= 500;

        return SendResult::failure((string) $error, $response->status(), $retryable);
    }

    public function status(): array
    {
        try {
            $response = $this->client()->get($this->baseUrl().'/status');

            if ($response->successful()) {
                return [
                    'status' => (string) ($response->json('wa_status') ?? 'unknown'),
                    'qr' => $response->json('qr'),
                    'phone' => $response->json('phone'),
                    'connected' => (bool) $response->json('connected', false),
                ];
            }
        } catch (ConnectionException $e) {
            Log::warning('wa.gateway.status_unreachable');
        }

        return [
            'status' => 'unreachable',
            'qr' => null,
            'phone' => null,
            'connected' => false,
        ];
    }

    public function logout(): bool
    {
        try {
            $response = $this->client()->post($this->baseUrl().'/logout');

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::warning('wa.gateway.logout_unreachable');

            return false;
        }
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['X-Gateway-Token' => (string) config('wa.gateway.token')])
            ->timeout((int) config('wa.gateway.timeout'))
            ->acceptJson();
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('wa.gateway.url'), '/');
    }
}
