<?php

namespace App\Livewire\Wa;

use App\Http\Controllers\Api\Wa\StatusController;
use App\Services\Wa\GatewayProcessManager;
use App\Services\Wa\QrCodeRenderer;
use App\Services\Wa\WhatsAppDriver;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

#[Layout('layouts.admin')]
class WhatsAppStatus extends Component
{
    use AuthorizesRequests;

    public array $status = [];

    public ?array $pushed = null;

    public ?string $qrImage = null;

    public ?string $qrPayload = null;

    public function mount(): void
    {
        $this->authorize('wa.read');
        $this->refresh();
    }

    #[On('gateway-process-changed')]
    public function refresh(): void
    {
        $this->status = app(WhatsAppDriver::class)->status();
        $this->pushed = Cache::get(StatusController::CACHE_KEY);
        $this->renderQrCode();
    }

    private function renderQrCode(): void
    {
        $qr = $this->status['qr'] ?? null;

        if (! is_string($qr) || $qr === '') {
            $this->qrPayload = null;
            $this->qrImage = null;

            return;
        }

        if ($qr === $this->qrPayload) {
            return;
        }

        try {
            $this->qrImage = app(QrCodeRenderer::class)->dataUri($qr);
            $this->qrPayload = $qr;
        } catch (Throwable $e) {
            $this->qrPayload = null;
            $this->qrImage = null;

            Log::warning('wa.qr.render_failed', ['message' => $e->getMessage()]);
        }
    }

    public function logout(): void
    {
        $this->authorize('wa.update');

        $ok = app(WhatsAppDriver::class)->logout();
        $processStopped = false;
        $processMessage = '';

        if ($ok) {
            $manager = app(GatewayProcessManager::class);

            if ($manager->enabled()) {
                $stop = $manager->stop();
                $processStopped = $stop['ok'];
                $processMessage = $stop['message'];
            }
        }

        $this->refresh();
        $this->dispatch('gateway-process-changed');

        if (! $ok) {
            $this->dispatch('notify', type: 'danger', message: 'Gagal logout dari gateway.');

            return;
        }

        if ($processStopped) {
            $this->dispatch('notify', type: 'success', message: 'Sesi WhatsApp telah di-logout dan gateway lokal dihentikan. Klik Nyalakan untuk start ulang dan scan QR baru.');

            return;
        }

        if ($processMessage !== '') {
            $this->dispatch('notify', type: 'warning', message: 'Sesi WhatsApp telah di-logout, tetapi proses gateway tidak dapat dihentikan: '.$processMessage);

            return;
        }

        $this->dispatch('notify', type: 'success', message: 'Sesi WhatsApp telah di-logout. Silakan scan QR baru di terminal gateway.');
    }

    public function render()
    {
        return view('livewire.wa.whatsapp-status', [
            'driver' => config('wa.driver'),
        ]);
    }
}
