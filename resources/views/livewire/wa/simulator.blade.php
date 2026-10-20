@php
    $bubbleClass = [
        'employee' => 'bubble-employee',
        'ai' => 'bubble-ai',
        'tech' => 'bubble-tech',
        'system' => 'bubble-system',
    ];
@endphp

<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Simulator Chat</h3>
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('dashboard') }}"><x-lucide-home style="width: 14px; height: 14px;" /></a>
            </li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">WhatsApp</a></li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">Simulator</a></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Percakapan</h5>
                    <button wire:click="resetSession" wire:confirm="Hapus seluruh chat dan tiket simulasi ini?"
                        class="btn btn-sm btn-label-warning rounded-3">
                        <x-lucide-rotate-ccw style="width: 14px; height: 14px;" class="me-1" /> Reset Chat
                    </button>
                </div>
                <div class="card-body">
                    @if ($notice)
                        <div class="alert alert-success py-2 mb-3">{{ $notice }}</div>
                    @endif

                    <div class="chat-box p-3 rounded-3" style="background:#f7f7f8; max-height:55vh; overflow-y:auto;">
                        @forelse ($thread as $item)
                            @php $isRight = $item['sender'] === 'tech'; @endphp
                            <div class="d-flex {{ $isRight ? 'justify-content-end' : 'justify-content-start' }} mb-2">
                                <div class="p-2 px-3 rounded-3 {{ $bubbleClass[$item['sender']] ?? 'bubble-system' }}" style="max-width: 80%;">
                                    <div class="small fw-semibold text-muted mb-1">
                                        {{ $item['sender'] === 'employee' ? 'Karyawan' : ($item['sender'] === 'ai' ? 'Asisten AI' : ucfirst($item['sender'])) }}
                                    </div>
                                    <div>{!! nl2br(e($item['body'])) !!}</div>
                                    <div class="text-muted small mt-1">{{ $item['time'] }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted py-4 mb-0">Belum ada percakapan. Kirim pesan untuk memulai.</p>
                        @endforelse
                    </div>
                </div>
                <div class="card-footer">
                    <form wire:submit="send" class="d-flex gap-2">
                        <input wire:model="message" type="text" class="form-control" placeholder="Ketik pesan sebagai karyawan...">
                        <button type="submit" class="btn btn-primary rounded-3 text-nowrap">
                            <x-lucide-send style="width: 14px; height: 14px;" class="me-1" /> Kirim
                        </button>
                    </form>
                    @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Persona Simulasi</h5>
                    <button wire:click="createPersona" class="btn btn-sm btn-label-primary rounded-3">
                        <x-lucide-plus style="width: 14px; height: 14px;" class="me-1" /> Tambah
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Persona</label>
                        <select wire:change="selectEmployee($event.target.value)" class="form-select">
                            @foreach ($personas as $item)
                                <option value="{{ $item->id }}" @selected($employeeId == $item->id)>
                                    {{ $item->name }} ({{ $item->phone }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($persona)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor Dummy</label>
                            <input type="text" class="form-control font-monospace" value="{{ $persona->phone }}" readonly>
                        </div>
                    @endif

                    <div class="alert alert-warning py-2 mb-0 small">
                        Simulasi memakai nomor dummy berawalan <strong>000</strong> yang bukan nomor WhatsApp asli.
                        Pesan dan tiket simulasi tidak dikirim ke WhatsApp maupun nomor notifikasi IT.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
