<div wire:poll.10s="refresh">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div class="fw-semibold">Queue Worker</div>
        <div class="d-flex flex-wrap gap-1">
            @if ($process['running'] ?? false)
                <span class="badge badge-success">
                    Aktif{{ ! empty($process['pid']) ? ' (PID '.$process['pid'].')' : '' }}
                </span>
            @else
                <span class="badge badge-secondary">Berhenti</span>
            @endif

            <span class="badge {{ $pending > 0 ? 'badge-warning' : 'badge-light' }}">{{ $pending }} pending</span>
            <span class="badge {{ $failed > 0 ? 'badge-danger' : 'badge-light' }}">{{ $failed }} gagal</span>
        </div>
    </div>

    <p class="text-muted small mb-2">
        {{ $process['message'] ?? '' }}
        @if ($heartbeatDisplay)
            <span class="ms-1">Heartbeat: {{ $heartbeatDisplay }}.</span>
        @endif
    </p>

    @if ($pending > 0 && ! ($process['running'] ?? false))
        <div class="alert alert-danger py-2 mb-3 small">
            Ada {{ $pending }} job menunggu dan queue worker tidak aktif. Pesan WhatsApp tidak akan diproses.
        </div>
    @endif

    @if ($enabled)
        @can('wa.update')
            <div class="d-flex flex-wrap gap-2">
                @if (! ($process['running'] ?? false))
                    <button wire:click="start" wire:loading.attr="disabled" class="btn btn-sm btn-success rounded-3">
                        <x-lucide-check-circle style="width: 14px; height: 14px;" class="me-1" /> Nyalakan Worker
                    </button>
                @elseif ($process['managed'] ?? false)
                    <button wire:click="stop" wire:confirm="Hentikan queue worker? Pesan baru tidak akan diproses sampai worker dinyalakan lagi."
                        wire:loading.attr="disabled" class="btn btn-sm btn-danger rounded-3">
                        <x-lucide-ban style="width: 14px; height: 14px;" class="me-1" /> Hentikan
                    </button>
                @endif

                @if ($process['managed'] ?? false)
                    <button wire:click="restart" wire:confirm="Restart queue worker?"
                        wire:loading.attr="disabled" class="btn btn-sm btn-warning rounded-3">
                        <x-lucide-rotate-ccw style="width: 14px; height: 14px;" class="me-1" /> Restart
                    </button>
                @endif

                <button wire:click="refresh" wire:loading.attr="disabled" class="btn btn-sm btn-label-secondary rounded-3">
                    <x-lucide-refresh-cw style="width: 14px; height: 14px;" class="me-1" /> Perbarui
                </button>
            </div>

            <div wire:loading wire:target="start,stop,restart" class="small text-muted mt-2">
                Memproses queue worker...
            </div>
        @endcan
    @else
        <div class="alert alert-info py-2 mb-0 small">
            Kontrol worker hanya aktif di lingkungan lokal. Di server, queue worker dikelola systemd/Supervisor.
        </div>
    @endif
</div>
