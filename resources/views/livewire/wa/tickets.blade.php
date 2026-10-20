@php
    $statusColors = [
        'OPEN' => 'badge-primary',
        'IN_PROGRESS' => 'badge-warning',
        'WAITING' => 'badge-info',
        'RESOLVED' => 'badge-success',
        'CLOSED' => 'badge-secondary',
    ];
    $priorityColors = [
        'low' => 'badge-secondary',
        'normal' => 'badge-primary',
        'high' => 'badge-warning',
        'urgent' => 'badge-danger',
    ];
@endphp

<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Tiket IT Support</h3>
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('dashboard') }}"><x-lucide-home style="width: 14px; height: 14px;" /></a>
            </li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">WhatsApp</a></li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">Tiket</a></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-3">Daftar Tiket</h4>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($categories as $c)
                            <span class="badge rounded-pill badge-light">
                                {{ ucfirst($c) }}: <strong>{{ $categoryCounts[$c] ?? 0 }}</strong>
                            </span>
                        @endforeach
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="input-group input-group-sm border rounded input-group-search">
                                <span class="input-group-text bg-transparent border-0 pe-0">
                                    <x-lucide-search style="width: 14px; height: 14px;" class="text-muted" />
                                </span>
                                <input wire:model.live.debounce.300ms="search" type="text"
                                    class="form-control border-0 ps-1 shadow-none" placeholder="Cari kode / judul / pelapor...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select wire:model.live="status" class="form-select form-select-sm">
                                <option value="">Semua Status</option>
                                @foreach ($statuses as $s)
                                    <option value="{{ $s }}">{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select wire:model.live="category" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select wire:model.live="priority" class="form-select form-select-sm">
                                <option value="">Semua Prioritas</option>
                                @foreach ($priorities as $p)
                                    <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select wire:model.live="assignee" class="form-select form-select-sm">
                                <option value="">Semua Teknisi</option>
                                @foreach ($technicians as $tech)
                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="display table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Pelapor</th>
                                    <th>Kategori</th>
                                    <th>Prioritas</th>
                                    <th>Status</th>
                                    <th>Teknisi</th>
                                    <th>Dibuat</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tickets as $ticket)
                                    <tr wire:key="ticket-{{ $ticket->id }}">
                                        <td class="fw-semibold">{{ $ticket->code }}</td>
                                        <td>
                                            <a href="{{ route('wa.tickets.show', $ticket) }}" class="text-decoration-none">
                                                {{ $ticket->title }}
                                            </a>
                                            @if ($ticket->employee?->is_simulated)
                                                <span class="badge rounded-pill badge-warning ms-1">SIMULASI</span>
                                            @endif
                                            @if ($ticket->unread_count > 0)
                                                <span class="badge rounded-pill badge-danger ms-1">{{ $ticket->unread_count }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $ticket->employee?->name }}</td>
                                        <td><span class="badge rounded-pill badge-light">{{ $ticket->category }}</span></td>
                                        <td><span class="badge {{ $priorityColors[$ticket->priority] ?? 'badge-secondary' }}">{{ $ticket->priority }}</span></td>
                                        <td><span class="badge {{ $statusColors[$ticket->status] ?? 'badge-secondary' }}">{{ $ticket->status }}</span></td>
                                        <td>{{ $ticket->assignee?->name ?? '-' }}</td>
                                        <td class="text-muted small">{{ $ticket->opened_at?->timezone(config('app.display_timezone'))?->format('d/m H:i') }}</td>
                                        <td class="text-nowrap">
                                            @can('ticket.update')
                                                @if (! $ticket->assignee_id)
                                                    <button wire:click="take({{ $ticket->id }})"
                                                        class="btn btn-sm btn-primary rounded-3">
                                                        Ambil Tiket
                                                    </button>
                                                @endif
                                            @endcan
                                            <a href="{{ route('wa.tickets.show', $ticket) }}"
                                                class="btn btn-sm btn-label-secondary rounded-3">Buka</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Belum ada tiket.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">Tampilkan</small>
                            <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 65px;">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <x-pagination :items="$tickets" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
