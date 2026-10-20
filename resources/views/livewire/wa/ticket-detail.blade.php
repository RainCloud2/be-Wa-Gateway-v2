@php
    $statusColors = [
        'OPEN' => 'badge-primary',
        'IN_PROGRESS' => 'badge-warning',
        'WAITING' => 'badge-info',
        'RESOLVED' => 'badge-success',
        'CLOSED' => 'badge-secondary',
    ];
    $bubbleClass = [
        'employee' => 'bubble-employee',
        'ai' => 'bubble-ai',
        'tech' => 'bubble-tech',
        'system' => 'bubble-system',
    ];
@endphp

<div wire:poll.5s>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Tiket {{ $ticket->code }}</h3>
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('dashboard') }}"><x-lucide-home style="width: 14px; height: 14px;" /></a>
            </li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="{{ route('wa.tickets.index') }}">Tiket</a></li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">{{ $ticket->code }}</a></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="badge {{ $statusColors[$ticket->status] ?? 'badge-secondary' }}">{{ $ticket->status }}</span>
                        <span class="badge rounded-pill badge-light">{{ $ticket->category }}</span>
                        <span class="badge rounded-pill badge-info">{{ $ticket->priority }}</span>
                        @if ($ticket->employee?->is_simulated)
                            <span class="badge rounded-pill badge-warning">SIMULASI</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        @if (! $ticket->assignee_id)
                            <button wire:click="assignToMe" class="btn btn-sm btn-primary rounded-3">Ambil Tiket</button>
                        @endif
                        @can('ticket.update')
                            @if (in_array($ticket->status, ['OPEN', 'IN_PROGRESS']))
                                <button wire:click="updateStatus('WAITING')" class="btn btn-sm btn-info rounded-3">Menunggu</button>
                            @endif
                            @if (in_array($ticket->status, ['OPEN', 'IN_PROGRESS', 'WAITING']))
                                <button wire:click="updateStatus('RESOLVED')" class="btn btn-sm btn-success rounded-3">Selesai</button>
                            @endif
                            @if (in_array($ticket->status, ['WAITING', 'RESOLVED', 'CLOSED']))
                                <button wire:click="updateStatus('IN_PROGRESS')" class="btn btn-sm btn-warning rounded-3">Buka Ulang</button>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if ($ticket->employee?->is_simulated)
                        <div class="alert alert-warning py-2 small">
                            Tiket simulasi. Pesan pada percakapan ini tidak dikirim ke WhatsApp.
                        </div>
                    @endif

                    <div class="chat-box" style="max-height: 60vh; overflow-y: auto;">
                        @forelse ($messages as $message)
                            @php
                                $isRight = $message->sender === 'tech';
                            @endphp
                            <div class="d-flex {{ $isRight ? 'justify-content-end' : 'justify-content-start' }} mb-2">
                                <div class="p-2 px-3 rounded-3 {{ $bubbleClass[$message->sender] ?? 'bubble-system' }}" style="max-width: 78%;">
                                    <div class="small fw-semibold text-muted mb-1">
                                        {{ $message->sender === 'employee' ? 'Karyawan' : ($message->sender === 'ai' ? 'Asisten AI' : ($message->sender === 'tech' ? (auth()->user()->name) : 'Sistem')) }}
                                    </div>
                                    <div>{!! nl2br(e($message->body)) !!}</div>
                                    <div class="d-flex justify-content-between align-items-center mt-1 gap-2">
                                        <span class="text-muted small">{{ $message->created_at->timezone(config('app.display_timezone'))->format('d/m H:i') }}</span>
                                        @if ($message->delivery_status === 'failed')
                                            <span class="text-danger small" title="Gagal terkirim">
                                                <x-lucide-alert-circle style="width: 13px; height: 13px;" /> Gagal
                                            </span>
                                        @elseif ($message->delivery_status === 'pending')
                                            <span class="text-muted small">
                                                <x-lucide-clock style="width: 13px; height: 13px;" /> Mengirim
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted py-4">Belum ada pesan.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @can('ticket.update')
                <div class="card">
                    <div class="card-body">
                        <form wire:submit="sendReply">
                            <label class="form-label fw-semibold">Balas Karyawan</label>
                            <textarea wire:model="reply" class="form-control" rows="3" placeholder="Tulis balasan untuk karyawan..."></textarea>
                            @error('reply') <small class="text-danger">{{ $message }}</small> @enderror
                            <div class="text-end mt-2">
                                <button type="submit" class="btn btn-primary rounded-3 btn-sm">
                                    <x-lucide-send style="width: 14px; height: 14px;" class="me-1" /> Kirim
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Ringkasan AI</h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Judul:</strong> {{ $ticket->title }}</p>
                    <p class="mb-0 text-muted">{{ $ticket->summary ?: 'Tidak ada ringkasan.' }}</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Detail</h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Pelapor:</strong> {{ $ticket->employee?->name }}<br>
                        <span class="text-muted font-monospace">{{ $ticket->employee?->phone }}</span>
                    </p>
                    <p class="mb-2"><strong>Departemen:</strong> {{ $ticket->employee?->department ?: '-' }}</p>
                    <p class="mb-2"><strong>Teknisi:</strong> {{ $ticket->assignee?->name ?? 'Belum ditugaskan' }}</p>
                    <p class="mb-2"><strong>Dibuat:</strong> {{ $ticket->opened_at?->timezone(config('app.display_timezone'))?->format('d/m/Y H:i') }}</p>
                    @if ($ticket->resolved_at)
                        <p class="mb-2"><strong>Selesai:</strong> {{ $ticket->resolved_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p>
                    @endif
                    @if ($ticket->closed_at)
                        <p class="mb-2"><strong>Ditutup:</strong> {{ $ticket->closed_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .bubble-employee { background: #f1f1f1; }
            .bubble-ai { background: #e7f1ff; }
            .bubble-tech { background: #d1e7dd; }
            .bubble-system { background: #fff3cd; }
            .chat-box { padding-right: 4px; }
        </style>
    @endpush
</div>
