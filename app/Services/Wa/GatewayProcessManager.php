<?php

namespace App\Services\Wa;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

class GatewayProcessManager
{
    public function enabled(): bool
    {
        return (bool) config('wa.process.enabled')
            && PHP_OS_FAMILY === 'Windows'
            && filled(config('wa.process.path'))
            && is_file(base_path('scripts/wa-gateway-process.ps1'));
    }

    /**
     * @return array{available: bool, running: bool, pid: ?int, message: string}
     */
    public function status(): array
    {
        if (! $this->enabled()) {
            return [
                'available' => false,
                'running' => false,
                'pid' => null,
                'message' => 'Kontrol proses gateway hanya aktif di lingkungan Windows lokal.',
            ];
        }

        $result = $this->run('status');

        return [
            'available' => true,
            'running' => (bool) ($result['running'] ?? false),
            'pid' => isset($result['pid']) ? (int) $result['pid'] : null,
            'message' => (string) ($result['message'] ?? ''),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function start(): array
    {
        if (! $this->enabled()) {
            return $this->unavailable();
        }

        if ($this->health()['ok']) {
            return ['ok' => true, 'message' => 'Gateway sudah berjalan.'];
        }

        $result = $this->run('start');

        if (! $result['ok']) {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Gagal menjalankan gateway.')];
        }

        if (! $this->waitUntilHealthy()) {
            return ['ok' => false, 'message' => 'Gateway dimulai, tetapi belum merespons. Periksa log gateway.'];
        }

        return ['ok' => true, 'message' => 'Gateway berhasil dijalankan.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function stop(): array
    {
        if (! $this->enabled()) {
            return $this->unavailable();
        }

        $result = $this->run('stop');

        if (! $result['ok']) {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Gagal menghentikan gateway.')];
        }

        if (! $this->waitUntilUnhealthy()) {
            return ['ok' => false, 'message' => 'Gateway masih merespons. Kemungkinan proses dijalankan di luar dashboard.'];
        }

        return ['ok' => true, 'message' => (string) ($result['message'] ?? 'Gateway berhasil dihentikan.')];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function restart(): array
    {
        if (! $this->enabled()) {
            return $this->unavailable();
        }

        $status = $this->status();

        if (! $status['running'] && $this->health()['ok']) {
            return ['ok' => false, 'message' => 'Gateway berjalan di luar dashboard. Hentikan prosesnya secara manual terlebih dahulu.'];
        }

        $result = $this->run('restart');

        if (! $result['ok']) {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Gagal me-restart gateway.')];
        }

        if (! $this->waitUntilHealthy()) {
            return ['ok' => false, 'message' => 'Gateway di-restart, tetapi belum merespons. Periksa log gateway.'];
        }

        return ['ok' => true, 'message' => 'Gateway berhasil di-restart.'];
    }

    /**
     * @return array{ok: bool, status: ?string}
     */
    public function health(): array
    {
        try {
            $response = Http::timeout(1)->acceptJson()->get($this->healthUrl());

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'status' => $response->json('wa_status'),
                ];
            }
        } catch (Throwable $e) {
            // The gateway is unreachable.
        }

        return ['ok' => false, 'status' => null];
    }

    /**
     * @return array{ok: bool, running: bool, pid: ?int, message: string}
     */
    private function run(string $action): array
    {
        $result = Process::timeout(20)->run([
            'powershell.exe',
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            base_path('scripts/wa-gateway-process.ps1'),
            '-Action',
            $action,
            '-GatewayPath',
            (string) config('wa.process.path'),
            '-NodePath',
            (string) config('wa.process.node'),
            '-Script',
            (string) config('wa.process.script'),
            '-PidFile',
            (string) config('wa.process.pid_file'),
            '-OutLog',
            (string) config('wa.process.out_log'),
            '-ErrLog',
            (string) config('wa.process.err_log'),
        ]);

        $decoded = json_decode(trim($result->output()), true);

        if (! is_array($decoded)) {
            Log::warning('wa.gateway.process_invalid_output', [
                'action' => $action,
                'output' => Str::limit($result->output().' '.$result->errorOutput(), 500),
            ]);

            return [
                'ok' => false,
                'running' => false,
                'pid' => null,
                'message' => 'Respons proses gateway tidak valid.',
            ];
        }

        $decoded = [
            'ok' => (bool) ($decoded['ok'] ?? false),
            'running' => (bool) ($decoded['running'] ?? false),
            'pid' => isset($decoded['pid']) ? (int) $decoded['pid'] : null,
            'message' => (string) ($decoded['message'] ?? ''),
        ];

        Log::info('wa.gateway.process', [
            'action' => $action,
            'ok' => $decoded['ok'],
            'running' => $decoded['running'],
        ]);

        return $decoded;
    }

    private function waitUntilHealthy(): bool
    {
        $deadline = microtime(true) + max(1, (int) config('wa.process.start_timeout', 10));

        do {
            if ($this->health()['ok']) {
                return true;
            }

            usleep(300000);
        } while (microtime(true) < $deadline);

        return false;
    }

    private function waitUntilUnhealthy(): bool
    {
        $deadline = microtime(true) + 3;

        do {
            if (! $this->health()['ok']) {
                return true;
            }

            usleep(300000);
        } while (microtime(true) < $deadline);

        return false;
    }

    private function healthUrl(): string
    {
        return rtrim((string) config('wa.gateway.url'), '/').'/health';
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function unavailable(): array
    {
        return [
            'ok' => false,
            'message' => 'Kontrol proses gateway hanya aktif di lingkungan Windows lokal.',
        ];
    }
}
