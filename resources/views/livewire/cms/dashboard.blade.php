<div>
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
        $waStatusColors = [
            'open' => 'badge-success',
            'connecting' => 'badge-warning',
            'qr' => 'badge-info',
            'close' => 'badge-danger',
            'logged_out' => 'badge-secondary',
        ];
    @endphp

    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        <div>
            <h3 class="fw-bold mb-3">Dashboard</h3>
            <h6 class="op-7 mb-2">Ringkasan WhatsApp dan tiket IT support</h6>
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            <a href="{{ route('wa.tickets.index') }}" class="btn btn-label-primary btn-round">
                <x-lucide-message-square style="width: 1em; height: 1em;" class="me-1" /> Lihat Tiket
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('wa.tickets.index') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small"><x-lucide-message-square style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Tiket</p>
                                    <h4 class="card-title">{{ number_format($totalTickets) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('wa.tickets.index') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small"><x-lucide-clock style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Tiket Aktif</p>
                                    <h4 class="card-title">{{ number_format($activeTickets) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('wa.tickets.index') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small"><x-lucide-check-circle style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Tiket Selesai</p>
                                    <h4 class="card-title">{{ number_format($completedTickets) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('wa.employees') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small"><x-lucide-users style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Whitelist Aktif</p>
                                    <h4 class="card-title">{{ number_format($activeEmployees) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row">
                        <div class="card-title">Tiket per Bulan</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="min-height: 320px"><canvas id="ticketMonthlyChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Status WhatsApp</div>
                        <a href="{{ route('wa.whatsapp') }}" class="btn btn-sm btn-label-secondary rounded-3">Detail</a>
                    </div>
                </div>
                <div class="card-body">
                    @if (is_array($waStatus))
                        <p class="mb-2">
                            <strong>Status:</strong>
                            <span class="badge {{ $waStatusColors[$waStatus['status'] ?? ''] ?? 'badge-secondary' }}">
                                {{ $waStatus['status'] ?? 'unknown' }}
                            </span>
                        </p>
                        <p class="mb-2"><strong>Nomor:</strong> {{ $waStatus['phone'] ?? '-' }}</p>
                        <p class="mb-0 text-muted small">
                            Diperbarui: {{ $waStatus['updated_at_display'] ?? '-' }}
                        </p>
                    @else
                        <p class="text-muted mb-0">Belum ada status yang dikirim gateway.</p>
                    @endif

                    <div class="border-top mt-3 pt-3">
                        <livewire:wa.gateway-control />
                    </div>

                    <div class="border-top mt-3 pt-3">
                        <livewire:wa.queue-worker-control />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Tiket Terbaru</div>
                        <a href="{{ route('wa.tickets.index') }}" class="btn btn-sm btn-label-secondary rounded-3">Semua Tiket</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Pelapor</th>
                                    <th>Status</th>
                                    <th>Prioritas</th>
                                    <th>Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTickets as $ticket)
                                    <tr>
                                        <td class="fw-semibold">{{ $ticket->code }}</td>
                                        <td>
                                            <a href="{{ route('wa.tickets.show', $ticket) }}" class="text-decoration-none">
                                                {{ $ticket->title }}
                                            </a>
                                            @if ($ticket->unread_count > 0)
                                                <span class="badge rounded-pill badge-danger ms-1">{{ $ticket->unread_count }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $ticket->employee?->name ?? '-' }}</td>
                                        <td><span class="badge {{ $statusColors[$ticket->status] ?? 'badge-secondary' }}">{{ $ticket->status }}</span></td>
                                        <td><span class="badge {{ $priorityColors[$ticket->priority] ?? 'badge-secondary' }}">{{ $ticket->priority }}</span></td>
                                        <td class="text-muted small">{{ $ticket->opened_at?->timezone(config('app.display_timezone'))?->format('d/m H:i') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada tiket.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Distribusi Status Tiket</div>
                    </div>
                </div>
                <div class="card-body">
                    @foreach ($statusDistribution as $row)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge {{ $statusColors[$row['status']] ?? 'badge-secondary' }}">{{ $row['status'] }}</span>
                                <small class="text-muted">{{ $row['total'] }} tiket</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                            <small class="text-muted">{{ $row['percent'] }}%</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        var monthLabels = @json($monthLabels);
        var monthData = @json($monthData);

        new Chart(document.getElementById('ticketMonthlyChart'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Tiket Baru',
                    data: monthData,
                    fill: true,
                    backgroundColor: 'rgba(23, 125, 255, 0.15)',
                    borderColor: '#177dff',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                },
            },
        });
    </script>
@endpush
