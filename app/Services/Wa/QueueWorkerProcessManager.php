<?php

namespace App\Services\Wa;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class QueueWorkerProcessManager
{
    public const HEARTBEAT_KEY = 'wa.queue.worker_heartbeat';

    public function enabled(): bool
    {
        return (bool) config('wa.queue_process.enabled')
            && PHP_OS_FAMILY === 'Windows'
            && is_file(base_path('scripts/wa-queue-worker.ps1'));
    }

    /**
     * @return array{
     *     available: bool,
     *     running: bool,
     *     managed: bool,
     *     pid: ?int,
     *     heartbeat_at: ?string,
     *     heartbeat_fresh: bool,
     *     message: string
     * }
     */
    public function status(): array
    {
        $heartbeat = $this->heartbeatAt();
        $heartbeatFresh = $this->heartbeatIsFresh($heartbeat);
        $process = ['running' => false, 'pid' => null, 'message' => ''];

        if ($this->enabled()) {
            $result = $this->run('status');
            $process = [
                'running' => (bool) ($result['running'] ?? false),
                'pid' => isset($result['pid']) ? (int) $result['pid'] : null,
                'message' => (string) ($result['message'] ?? ''),
            ];
        }

        $running = $process['running'] || $heartbeatFresh;

        return [
            'available' => $this->enabled(),
            'running' => $running,
            'managed' => $process['running'],
            'pid' => $process['pid'],
            'heartbeat_at' => $heartbeat?->toIso8601String(),
            'heartbeat_fresh' => $heartbeatFresh,
            'message' => $heartbeatFresh
                ? 'Queue worker aktif.'
                : ($process['message'] ?: 'Queue worker tidak terdeteksi.'),
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

        if ($this->heartbeatIsFresh()) {
            return ['ok' => true, 'message' => 'Queue worker sudah berjalan.'];
        }

        $result = $this->run('start');

        if (! $result['ok']) {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Gagal menjalankan queue worker.')];
        }

        if (! $this->waitUntilHeartbeat()) {
            return ['ok' => false, 'message' => 'Queue worker dimulai, tetapi heartbeat belum terdeteksi. Periksa log worker.'];
        }

        return ['ok' => true, 'message' => 'Queue worker berhasil dijalankan.'];
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
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Gagal menghentikan queue worker.')];
        }

        Cache::forget(self::HEARTBEAT_KEY);

        return ['ok' => true, 'message' => (string) ($result['message'] ?? 'Queue worker berhasil dihentikan.')];
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

        if ($status['heartbeat_fresh'] && ! $status['managed']) {
            return ['ok' => false, 'message' => 'Queue worker berjalan di luar dashboard. Hentikan prosesnya secara manual terlebih dahulu.'];
        }

        $result = $this->run('restart');

        if (! $result['ok']) {
            return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Gagal me-restart queue worker.')];
        }

        Cache::forget(self::HEARTBEAT_KEY);

        if (! $this->waitUntilHeartbeat()) {
            return ['ok' => false, 'message' => 'Queue worker di-restart, tetapi heartbeat belum terdeteksi. Periksa log worker.'];
        }

        return ['ok' => true, 'message' => 'Queue worker berhasil di-restart.'];
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
            base_path('scripts/wa-queue-worker.ps1'),
            '-Action',
            $action,
            '-PhpPath',
            (string) config('wa.queue_process.php'),
            '-AppPath',
            base_path(),
            '-Tries',
            (string) config('wa.queue_process.tries', 3),
            '-RunnerPath',
            base_path('scripts/wa-queue-worker-run.ps1'),
        ]);

        $decoded = json_decode(trim($result->output()), true);

        if (! is_array($decoded)) {
            Log::warning('wa.queue.process_invalid_output', [
                'action' => $action,
                'output' => Str::limit($result->output().' '.$result->errorOutput(), 500),
            ]);

            return [
                'ok' => false,
                'running' => false,
                'pid' => null,
                'message' => 'Respons proses queue worker tidak valid.',
            ];
        }

        $decoded = [
            'ok' => (bool) ($decoded['ok'] ?? false),
            'running' => (bool) ($decoded['running'] ?? false),
            'pid' => isset($decoded['pid']) ? (int) $decoded['pid'] : null,
            'message' => (string) ($decoded['message'] ?? ''),
        ];

        Log::info('wa.queue.process', [
            'action' => $action,
            'ok' => $decoded['ok'],
            'running' => $decoded['running'],
        ]);

        return $decoded;
    }

    private function waitUntilHeartbeat(): bool
    {
        $deadline = microtime(true) + max(1, (int) config('wa.queue_process.start_timeout', 10));

        do {
            if ($this->heartbeatIsFresh()) {
                return true;
            }

            usleep(300000);
        } while (microtime(true) < $deadline);

        return false;
    }

    private function heartbeatAt(): ?Carbon
    {
        $value = Cache::get(self::HEARTBEAT_KEY);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function heartbeatIsFresh(?Carbon $heartbeat = null): bool
    {
        $heartbeat ??= $this->heartbeatAt();

        if (! $heartbeat) {
            return false;
        }

        $staleSeconds = max(10, (int) config('wa.queue_process.heartbeat_stale_seconds', 60));

        return $heartbeat->gt(now()->subSeconds($staleSeconds));
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function unavailable(): array
    {
        return [
            'ok' => false,
            'message' => 'Kontrol queue worker hanya aktif di lingkungan Windows lokal.',
        ];
    }
}
