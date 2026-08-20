<div>
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
        <div>
            <h3 class="fw-bold mb-3">Dashboard</h3>
            <h6 class="op-7 mb-2">Ringkasan data pekerja</h6>
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            <a href="{{ route('master.workers') }}" class="btn btn-label-primary btn-round">
                <x-lucide-users style="width: 1em; height: 1em;" class="me-1" /> Lihat Pekerja
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('master.workers') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small"><x-lucide-users style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Pekerja</p>
                                    <h4 class="card-title">{{ number_format($totalWorkers) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('master.banks') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small"><x-lucide-landmark style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Bank</p>
                                    <h4 class="card-title">{{ number_format($totalBanks) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('account.users') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small"><x-lucide-user-check style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total User</p>
                                    <h4 class="card-title">{{ number_format($totalUsers) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="{{ route('master.workers') }}" class="text-decoration-none">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small"><x-lucide-calendar-plus style="width: 1.5em; height: 1.5em;" /></div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Pekerja Baru Bulan Ini</p>
                                    <h4 class="card-title">{{ number_format($newWorkersThisMonth) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row">
                        <div class="card-title">Distribusi Pekerja per Bank</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="min-height: 320px"><canvas id="workerBankChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row">
                        <div class="card-title">Pekerja Baru per Bulan</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="min-height: 320px"><canvas id="workerMonthlyChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Pekerja Terbaru</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-list py-3">
                        @forelse ($recentWorkers as $worker)
                            <div class="item-list">
                                <div class="avatar">
                                    @if ($worker->self_photo)
                                        <img src="{{ Storage::url($worker->self_photo) }}" alt="{{ $worker->full_name }}" class="avatar-img rounded-circle" />
                                    @else
                                        <span class="avatar-title rounded-circle border border-white bg-primary">
                                            {{ strtoupper(substr($worker->full_name, 0, 1)) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="info-user ms-3">
                                    <div class="username">{{ $worker->full_name }}</div>
                                    <div class="status">{{ $worker->ops_id }} · {{ $worker->bank?->initial ?? 'Tanpa Bank' }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Belum ada pekerja.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card card-round">
                <div class="card-header">
                    <div class="card-head-row card-tools-still-right">
                        <div class="card-title">Distribusi Pekerja per Bank</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Bank</th>
                                    <th class="text-end">Pekerja</th>
                                    <th style="width: 35%">Presentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bankTable as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row->name }}</td>
                                        <td class="text-end">{{ $row->total }}</td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-primary" role="progressbar"
                                                    style="width: {{ $row->percent }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $row->percent }}%</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        var bankLabels = @json($bankLabels);
        var bankData = @json($bankData);
        var monthLabels = @json($monthLabels);
        var monthData = @json($monthData);

        new Chart(document.getElementById('workerBankChart'), {
            type: 'bar',
            data: {
                labels: bankLabels,
                datasets: [{
                    label: 'Pekerja',
                    data: bankData,
                    backgroundColor: 'rgba(23, 125, 255, 0.7)',
                    borderColor: '#177dff',
                    borderWidth: 1,
                    borderRadius: 4,
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

        new Chart(document.getElementById('workerMonthlyChart'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Pekerja Baru',
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
