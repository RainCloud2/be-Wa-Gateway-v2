<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Pengaturan Chatbot</h3>
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('dashboard') }}"><x-lucide-home style="width: 14px; height: 14px;" /></a>
            </li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">WhatsApp</a></li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">Pengaturan</a></li>
        </ul>
    </div>

    <form wire:submit="save">
        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Perilaku AI</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">System Prompt</label>
                            <textarea wire:model="system_prompt" class="form-control" rows="10"></textarea>
                            @error('system_prompt') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pesan Sambutan</label>
                            <textarea wire:model="welcome_message" class="form-control" rows="2"></textarea>
                            @error('welcome_message') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Maks. Percobaan AI</label>
                                <input wire:model="max_attempts" type="number" min="1" max="20" class="form-control">
                                @error('max_attempts') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Timeout Sesi (jam)</label>
                                <input wire:model="session_timeout_hours" type="number" min="1" class="form-control">
                                @error('session_timeout_hours') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Jam Kerja & Notifikasi</h5></div>
                    <div class="card-body">
                        <label class="form-label fw-semibold d-block">Hari Kerja</label>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach ($dayNames as $num => $name)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $num }}"
                                        id="day-{{ $num }}" wire:model="business_days">
                                    <label class="form-check-label" for="day-{{ $num }}">{{ $name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-semibold">Mulai</label>
                                <input wire:model="business_start" type="time" class="form-control">
                                @error('business_start') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-semibold">Selesai</label>
                                <input wire:model="business_end" type="time" class="form-control">
                                @error('business_end') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Zona Waktu</label>
                            <input wire:model="business_timezone" type="text" class="form-control">
                            @error('business_timezone') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor Notifikasi IT</label>
                            <textarea wire:model="it_notify_numbers" class="form-control" rows="3"
                                placeholder="628111111111&#10;628222222222"></textarea>
                            <small class="text-muted">Satu nomor per baris (format 628xxx).</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Auto-close Tiket Resolved (jam)</label>
                            <input wire:model="auto_close_resolved_hours" type="number" min="1" class="form-control">
                            @error('auto_close_resolved_hours') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>

                @can('setting.update')
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-3">
                            <x-lucide-save style="width: 16px; height: 16px;" class="me-1" /> Simpan Pengaturan
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    </form>
</div>
