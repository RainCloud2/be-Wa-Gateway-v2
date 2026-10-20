<div @if (in_array($status['status'] ?? '', ['qr', 'connecting', 'close'], true)) wire:poll.5s="refresh" @endif>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Status WhatsApp</h3>
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('dashboard') }}"><x-lucide-home style="width: 14px; height: 14px;" /></a>
            </li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">WhatsApp</a></li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">Status</a></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Koneksi Gateway</h5>
                    <button wire:click="refresh" class="btn btn-sm btn-label-primary rounded-3">
                        <x-lucide-refresh-cw style="width: 14px; height: 14px;" class="me-1" /> Perbarui
                    </button>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Driver aktif:</strong> <span class="badge badge-dark">{{ $driver }}</span></p>

                    @if ($driver === 'log')
                        <div class="alert alert-warning">
                            Driver <strong>log</strong> aktif. Pesan tidak dikirim ke WhatsApp. Ubah
                            <code>WA_DRIVER=gateway</code> di <code>.env</code> untuk integrasi sungguhan.
                        </div>
                    @endif

                    <p class="mb-2"><strong>Status:</strong>
                        @php $s = $status['status'] ?? 'unknown'; @endphp
                        <span class="badge {{ $s === 'open' ? 'badge-success' : ($s === 'qr' || $s === 'connecting' ? 'badge-warning' : 'badge-danger') }}">
                            {{ $s }}
                        </span>
                    </p>
                    <p class="mb-2"><strong>Terhubung:</strong> {{ ($status['connected'] ?? false) ? 'Ya' : 'Tidak' }}</p>
                    <p class="mb-2"><strong>Nomor:</strong> {{ $status['phone'] ?? '-' }}</p>

                    @if ($qrImage)
                        <div class="mt-3 text-center">
                            <label class="form-label fw-semibold d-block">QR WhatsApp</label>
                            <img src="{{ $qrImage }}" alt="QR WhatsApp" width="320" height="320"
                                class="img-fluid border rounded-3 bg-white p-2" style="max-width: 320px; height: auto;">
                            <small class="d-block text-muted mt-2">
                                Scan melalui WhatsApp &rarr; Perangkat tertaut. QR diperbarui otomatis.
                            </small>
                            <details class="mt-2 text-start">
                                <summary class="small text-muted">Payload QR</summary>
                                <textarea class="form-control font-monospace mt-2" rows="3" readonly>{{ $status['qr'] }}</textarea>
                            </details>
                        </div>
                    @elseif (! empty($status['qr']))
                        <div class="alert alert-warning py-2 mt-3 mb-0 small">
                            QR diterima dari gateway tetapi gagal dirender. Periksa log aplikasi.
                        </div>
                    @endif
                </div>
                @can('wa.update')
                    <div class="card-footer">
                        <button wire:click="logout"
                            wire:confirm="Logout akan memutus sesi WhatsApp dan menghentikan proses gateway lokal. Lanjutkan?"
                            class="btn btn-danger rounded-3 btn-sm">
                            <x-lucide-log-out style="width: 14px; height: 14px;" class="me-1" /> Logout / Ganti Nomor
                        </button>
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Status Terakhir dari Gateway</h5></div>
                <div class="card-body">
                    @if ($pushed)
                        <p class="mb-1"><strong>Status:</strong> {{ $pushed['status'] ?? '-' }}</p>
                        <p class="mb-1"><strong>Nomor:</strong> {{ $pushed['phone'] ?? '-' }}</p>
                        <p class="mb-0 text-muted small">Diperbarui:
                            {{ isset($pushed['updated_at']) ? \Illuminate\Support\Carbon::parse($pushed['updated_at'])->timezone(config('app.display_timezone'))->format('d/m/Y H:i') : '-' }}
                        </p>
                    @else
                        <p class="text-muted mb-0">Belum ada status yang dikirim gateway.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Kontrol Proses Gateway</h5></div>
                <div class="card-body">
                    <livewire:wa.gateway-control />
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Queue Worker</h5></div>
                <div class="card-body">
                    <livewire:wa.queue-worker-control />
                </div>
            </div>
        </div>
    </div>
</div>
