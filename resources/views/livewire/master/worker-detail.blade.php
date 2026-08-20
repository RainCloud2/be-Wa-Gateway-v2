<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Worker Detail</h3>
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('dashboard') }}">
                    <x-lucide-home style="width: 14px; height: 14px;" />
                </a>
            </li>
            <li class="separator">
                <x-lucide-chevron-right style="width: 12px; height: 12px;" />
            </li>
            <li class="nav-item">
                <a href="#">Master Data</a>
            </li>
            <li class="separator">
                <x-lucide-chevron-right style="width: 12px; height: 12px;" />
            </li>
            <li class="nav-item">
                <a href="{{ route('master.workers') }}">Workers</a>
            </li>
            <li class="separator">
                <x-lucide-chevron-right style="width: 12px; height: 12px;" />
            </li>
            <li class="nav-item">
                <a href="#">{{ $worker->ops_id }}</a>
            </li>
        </ul>
    </div>

    {{-- Search & Back --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div class="w-100" style="max-width: 380px;">
            <div class="input-group input-group-sm border rounded-3 shadow-sm input-group-search position-relative"
                style="height: 31px;">
                <span class="input-group-text bg-transparent border-0 pe-0">
                    <x-lucide-search style="width: 14px; height: 14px;" class="text-muted" />
                </span>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="form-control border-0 ps-1 shadow-none" placeholder="Search by Ops ID, Name or NIK...">

                @if (trim($search) !== '')
                    <div class="dropdown-menu show position-absolute w-100 shadow-sm border-0"
                        style="top: 100%; z-index: 1050; max-height: 300px; overflow-y: auto;">
                        @forelse ($results as $result)
                            <a href="#" wire:click.prevent="goTo({{ $result->id }})"
                                class="dropdown-item d-flex align-items-center gap-2 py-2">
                                @if ($result->self_photo)
                                    <img src="{{ Storage::url($result->self_photo) }}" class="rounded-circle"
                                        style="width: 28px; height: 28px; object-fit: cover;" />
                                @else
                                    <span
                                        class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width: 28px; height: 28px;">
                                        <x-lucide-user style="width: 14px; height: 14px;" class="text-muted" />
                                    </span>
                                @endif
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">{{ $result->full_name }}</span>
                                    <small class="text-muted">{{ $result->ops_id }} • {{ $result->nik }}</small>
                                </div>
                            </a>
                        @empty
                            <span class="dropdown-item-text text-muted small">No worker found.</span>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        <a href="{{ route('master.workers') }}"
            class="btn btn-outline-secondary rounded-3 btn-sm d-inline-flex align-items-center flex-shrink-0"
            style="height: 31px;">
            <x-lucide-arrow-left style="width: 14px; height: 14px;" class="me-1" /> Back to List
        </a>
    </div>

    {{-- Main Detail Card --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-round shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="row gy-4">
                        {{-- Profile column --}}
                        <div class="col-md-4 text-center border-end-md">
                            <div class="d-flex flex-column align-items-center">
                                @if ($worker->self_photo)
                                    <img src="{{ Storage::url($worker->self_photo) }}"
                                        class="rounded-circle shadow-sm border border-3 border-white"
                                        style="width: 160px; height: 160px; object-fit: cover;" />
                                @else
                                    <span
                                        class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center shadow-sm"
                                        style="width: 160px; height: 160px;">
                                        <x-lucide-user style="width: 64px; height: 64px;" class="text-muted" />
                                    </span>
                                @endif
                                <span class="badge rounded-pill badge-success mt-3">{{ $worker->ops_id }}</span>
                                <h4 class="fw-bold mt-2 mb-0">{{ $worker->full_name }}</h4>
                                <p class="text-muted mb-0">NIK: {{ $worker->nik }}</p>
                                @if ($worker->phone_number)
                                    <p class="text-muted mb-0 mt-1">No. HP: {{ $worker->phone_number }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Info column --}}
                        <div class="col-md-8">
                            <h6 class="fw-bold text-uppercase text-muted mb-3"
                                style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                Bank & Address
                            </h6>
                            <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                                <div class="col">
                                    <div class="bg-light rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-1"
                                            style="font-size: 0.7rem;">Bank</small>
                                        <p class="mb-0 fw-semibold">{{ $worker->bank?->name ?? '-' }}</p>
                                        @if ($worker->bank?->initial)
                                            <small class="text-muted">{{ $worker->bank->initial }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="bg-light rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-1"
                                            style="font-size: 0.7rem;">Account Number</small>
                                        <p class="mb-0 fw-semibold">{{ $worker->account_number ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="bg-light rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-1"
                                            style="font-size: 0.7rem;">Domicile Address</small>
                                        <p class="mb-0 fw-semibold">{{ $worker->domicile_address ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="fw-bold d-flex align-items-center gap-2 mb-3">
                                <x-lucide-phone-call style="width: 16px; height: 16px;" class="text-muted" />
                                Emergency Contact
                            </h6>
                            <div class="row row-cols-1 row-cols-md-3 g-3">
                                <div class="col">
                                    <div class="bg-light rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-1"
                                            style="font-size: 0.7rem;">Contact Number</small>
                                        <p class="mb-0 fw-semibold">{{ $worker->emergency_contact_number ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="bg-light rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-1"
                                            style="font-size: 0.7rem;">Contact Name</small>
                                        <p class="mb-0 fw-semibold">{{ $worker->emergency_contact_name ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="bg-light rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-semibold d-block mb-1"
                                            style="font-size: 0.7rem;">Relation</small>
                                        <p class="mb-0 fw-semibold">{{ $worker->emergency_contact_relation ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Document Photos --}}
                    <h6 class="fw-bold text-uppercase text-muted mb-3"
                        style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        Document Photos
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <x-lucide-id-card style="width: 16px; height: 16px;" class="text-muted" />
                                    <span class="fw-semibold small">KTP Photo</span>
                                </div>
                                @if ($worker->ktp_photo)
                                    <a href="{{ Storage::url($worker->ktp_photo) }}" target="_blank"
                                        class="d-block">
                                        <img src="{{ Storage::url($worker->ktp_photo) }}"
                                            class="img-fluid rounded-3 w-100"
                                            style="height: 220px; object-fit: cover;" />
                                    </a>
                                @else
                                    <div class="border rounded-3 bg-light d-flex align-items-center justify-content-center text-muted"
                                        style="height: 220px;">
                                        <x-lucide-id-card style="width: 48px; height: 48px;" />
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <x-lucide-user style="width: 16px; height: 16px;" class="text-muted" />
                                    <span class="fw-semibold small">Self Photo</span>
                                </div>
                                @if ($worker->self_photo)
                                    <a href="{{ Storage::url($worker->self_photo) }}" target="_blank"
                                        class="d-block">
                                        <img src="{{ Storage::url($worker->self_photo) }}"
                                            class="img-fluid rounded-3 w-100"
                                            style="height: 220px; object-fit: cover;" />
                                    </a>
                                @else
                                    <div class="border rounded-3 bg-light d-flex align-items-center justify-content-center text-muted"
                                        style="height: 220px;">
                                        <x-lucide-user style="width: 48px; height: 48px;" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
