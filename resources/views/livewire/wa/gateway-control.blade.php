<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div class="fw-semibold">Proses Gateway</div>
        <div class="d-flex flex-wrap gap-1">
            @if (! $enabled)
                <span class="badge badge-secondary">Di luar dashboard</span>
            @elseif ($process['running'] ?? false)
                <span class="badge badge-success">
                    Berjalan{{ ! empty($process['pid']) ? ' (PID '.$process['pid'].')' : '' }}
                </span>
            @else
                <span class="badge badge-secondary">Berhenti</span>
            @endif
        </div>
    </div>

    <p class="text-muted small mb-3">
        {{ $process['message'] ?? '' }}
    </p>

    @if ($enabled)
        @can('wa.update')
            <div class="d-flex flex-wrap gap-2">
                @if (! ($process['running'] ?? false))
                    <button wire:click="start" wire:loading.attr="disabled" class="btn btn-sm btn-success rounded-3">
                        <x-lucide-check-circle style="width: 14px; height: 14px;" class="me-1" /> Nyalakan
                    </button>
                @else
                    <button wire:click="stop" wire:confirm="Hentikan proses gateway WhatsApp?"
                        wire:loading.attr="disabled" class="btn btn-sm btn-danger rounded-3">
                        <x-lucide-ban style="width: 14px; height: 14px;" class="me-1" /> Hentikan
                    </button>
                @endif

                <button wire:click="restart" wire:confirm="Restart proses gateway WhatsApp?"
                    wire:loading.attr="disabled" class="btn btn-sm btn-warning rounded-3">
                    <x-lucide-rotate-ccw style="width: 14px; height: 14px;" class="me-1" /> Restart
                </button>

                <button wire:click="refresh" wire:loading.attr="disabled" class="btn btn-sm btn-label-secondary rounded-3">
                    <x-lucide-refresh-cw style="width: 14px; height: 14px;" class="me-1" /> Perbarui
                </button>
            </div>

            <div wire:loading wire:target="start,stop,restart" class="small text-muted mt-2">
                Memproses gateway...
            </div>
        @endcan
    @else
        <div class="alert alert-info py-2 mb-0 small">
            Kontrol proses hanya aktif di lingkungan lokal. Di server, gateway dikelola oleh systemd/PM2/Docker.
        </div>
    @endif
</div>
