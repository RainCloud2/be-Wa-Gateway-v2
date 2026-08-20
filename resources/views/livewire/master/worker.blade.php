<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Worker Data</h3>
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
                <a href="#">Workers</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <div class="d-flex gap-2">
                            @if ($selectMode)
                                @can('worker.delete')
                                    <button wire:click="toggleSelectMode"
                                        class="btn btn-secondary rounded-3 btn-sm">Cancel</button>
                                    <button wire:click="confirmBulkDelete" class="btn btn-danger rounded-3 btn-sm"
                                        x-bind:disabled="$wire.selectedIds.length === 0">
                                        <x-lucide-trash-2 style="width: 14px; height: 14px;" class="me-1" /> Delete
                                        Selected
                                    </button>
                                @endcan
                            @else
                                @can('worker.create')
                                    <button wire:click="create" class="btn btn-primary rounded-3 btn-sm">
                                        <x-lucide-plus style="width: 14px; height: 14px;" class="me-1" /> Add Worker
                                    </button>
                                @endcan
                                @can('worker.delete')
                                    <button wire:click="toggleSelectMode"
                                        class="btn btn-outline-secondary rounded-3 btn-sm">
                                        <x-lucide-check-square style="width: 14px; height: 14px;" class="me-1" /> Select
                                    </button>
                                @endcan
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            @can('worker.import')
                                <button wire:click="$dispatch('show-import-modal')"
                                    class="btn btn-label-info rounded-3 btn-sm">
                                    <x-lucide-upload style="width: 14px; height: 14px;" class="me-1" /> Import
                                </button>
                            @endcan
                            @can('worker.export')
                                <button wire:click="export" class="btn btn-label-success rounded-3 btn-sm">
                                    <x-lucide-download style="width: 14px; height: 14px;" class="me-1" /> Export
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">Show</small>
                            <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 65px;">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <small class="text-muted">entries</small>
                        </div>
                        <div class="input-group input-group-sm border rounded input-group-search" style="width: 220px;">
                            <span class="input-group-text bg-transparent border-0 pe-0">
                                <x-lucide-search style="width: 14px; height: 14px;" class="text-muted" />
                            </span>
                            <input wire:model.live.debounce.300ms="search" type="text"
                                class="form-control border-0 ps-1 shadow-none" placeholder="Search...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="display table table-striped table-hover">
                            <thead>
                                <tr>
                                    @if ($selectMode)
                                        <th style="width: 40px;"><input type="checkbox" wire:click="toggleSelectAll"
                                                x-bind:checked="$wire.selectedIds.length === {{ $workers->total() }}">
                                        </th>
                                    @endif
                                    <th>#</th>
                                    <th>Ops ID</th>
                                    <th>Name</th>
                                    <th>No. HP</th>
                                    <th>Bank</th>
                                    <th>Account Number</th>
                                    @can('worker.delete' || 'worker.update')
                                        <th>Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($workers as $worker)
                                    <tr>
                                        @if ($selectMode)
                                            <td><input type="checkbox" wire:model="selectedIds"
                                                    value="{{ $worker->id }}" wire:key="worker-{{ $worker->id }}">
                                            </td>
                                        @endif
                                        <td>{{ $loop->iteration + ($workers->currentPage() - 1) * $workers->perPage() }}
                                        </td>
                                        <td><span class="badge rounded-pill badge-success">{{ $worker->ops_id }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ $worker->full_name }}</span>
                                                <small class="text-muted">NIK: {{ $worker->nik }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($worker->phone_number)
                                                @php
                                                    $wa = preg_replace('/[^0-9]/', '', $worker->phone_number);
                                                    if (str_starts_with($wa, '0')) {
                                                        $wa = '62'.substr($wa, 1);
                                                    }
                                                @endphp
                                                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"
                                                    class="badge rounded-pill d-inline-flex align-items-center gap-1"
                                                    style="background-color:#25D366; color:#fff;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                        fill="currentColor" style="width:12px; height:12px;">
                                                        <path
                                                            d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm5.83 14.12c-.25.7-1.45 1.33-2.02 1.42-.52.08-1.17.12-1.9-.12-.44-.15-1.02-.33-1.75-.65-3.06-1.32-5.05-4.4-5.2-4.6-.15-.2-1.24-1.65-1.24-3.15s.79-2.23 1.07-2.53c.28-.3.61-.38.81-.38h.58c.19 0 .44-.07.69.53.25.6.85 2.08.93 2.23.08.15.13.33.02.53-.1.2-.15.33-.3.5-.15.18-.32.4-.46.54-.15.15-.31.32-.13.63.18.3.79 1.3 1.7 2.11 1.17 1.04 2.16 1.36 2.47 1.51.3.15.48.13.66-.08.18-.2.76-.88.96-1.18.2-.3.4-.25.68-.15.28.1 1.76.83 2.06.98.3.15.5.23.58.35.07.13.07.73-.18 1.43z" />
                                                    </svg>
                                                    {{ $worker->phone_number }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill badge-secondary">{{ $worker->bank?->initial ?? '-' }}</span>
                                        </td>
                                        <td>{{ $worker->account_number ?? '-' }}</td>
                                        @can('worker.delete' || 'worker.update')
                                            <td>
                                                <div class="d-flex align-items-center gap-1 text-nowrap">
                                                    <button wire:click="showDetail({{ $worker->id }})"
                                                        class="btn btn-link btn-primary btn-xs p-1">
                                                        <x-lucide-eye style="width: 16px; height: 16px;" />
                                                    </button>
                                                    <button wire:click="edit({{ $worker->id }})"
                                                        class="btn btn-link btn-primary btn-xs p-1">
                                                        <x-lucide-pencil style="width: 16px; height: 16px;" />
                                                    </button>
                                                    <button
                                                        wire:click="confirmDelete({{ $worker->id }}, '{{ $worker->full_name }}')"
                                                        class="btn btn-link btn-danger btn-xs p-1">
                                                        <x-lucide-trash-2 style="width: 16px; height: 16px;" />
                                                    </button>
                                                </div>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No workers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :items="$workers" />
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">Import</span>
                        <span class="fw-light"> Workers</span>
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <form wire:submit="import">
                    <div class="modal-body">
                        <div class="mb-3">
                            <a href="#" wire:click.prevent="downloadTemplate" class="btn btn-link btn-sm ps-0">
                                <x-lucide-download style="width: 14px; height: 14px;" class="me-1" /> Download
                                Template
                            </a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Excel File</label>
                            <input type="file" wire:model="importFile" class="form-control"
                                accept=".xlsx,.xls,.csv">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <x-lucide-upload style="width: 14px; height: 14px;" class="me-1" /> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="workerModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">{{ $editMode ? 'Edit' : 'New' }}</span>
                        <span class="fw-light"> Worker</span>
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <form wire:submit.prevent="{{ $editMode ? 'update' : 'store' }}">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Ops ID <span
                                            class="text-danger">*</span></label>
                                    <input wire:model="opsId" type="text" class="form-control">
                                    @error('opsId') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">NIK <span class="text-danger">*</span></label>
                                    <input wire:model="nik" type="text" class="form-control" maxlength="20">
                                    @error('nik') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Lengkap <span
                                            class="text-danger">*</span></label>
                                    <input wire:model="fullName" type="text" class="form-control">
                                    @error('fullName') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">No. HP</label>
                                    <input wire:model="phoneNumber" type="text" class="form-control">
                                    @error('phoneNumber') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Bank</label>
                                    <x-bank-select :banks="$banks" model="bankId" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nomor Rekening</label>
                                    <input wire:model="accountNumber" type="text" class="form-control">
                                    @error('accountNumber') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Alamat Domisili</label>
                            <textarea wire:model="domicileAddress" class="form-control" rows="2"></textarea>
                            @error('domicileAddress') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <hr>
                        <h6 class="fw-bold mb-3">Kontak Darurat</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nomor Kontak</label>
                                    <input wire:model="emergencyContactNumber" type="text" class="form-control">
                                    @error('emergencyContactNumber') <small
                                        class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Kontak</label>
                                    <input wire:model="emergencyContactName" type="text" class="form-control">
                                    @error('emergencyContactName') <small
                                        class="text-danger">{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hubungan</label>
                            <input wire:model="emergencyContactRelation" type="text" class="form-control"
                                placeholder="cth: Suami/Istri, Orang Tua">
                            @error('emergencyContactRelation') <small
                                class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Foto KTP</label>
                                    <input type="file" wire:model="ktpPhoto" class="form-control"
                                        accept="image/*" capture>
                                    @error('ktpPhoto') <small class="text-danger">{{ $message }}</small> @enderror
                                    @if ($ktpPhoto)
                                        <img src="{{ $ktpPhoto->temporaryUrl() }}" class="img-fluid mt-2 rounded"
                                            style="max-height: 140px;" />
                                    @elseif ($currentKtpPhoto)
                                        <img src="{{ Storage::url($currentKtpPhoto) }}"
                                            class="img-fluid mt-2 rounded" style="max-height: 140px;" />
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Foto Diri</label>
                                    <input type="file" wire:model="selfPhoto" class="form-control"
                                        accept="image/*" capture>
                                    @error('selfPhoto') <small class="text-danger">{{ $message }}</small> @enderror
                                    @if ($selfPhoto)
                                        <img src="{{ $selfPhoto->temporaryUrl() }}" class="img-fluid mt-2 rounded"
                                            style="max-height: 140px;" />
                                    @elseif ($currentSelfPhoto)
                                        <img src="{{ Storage::url($currentSelfPhoto) }}"
                                            class="img-fluid mt-2 rounded" style="max-height: 140px;" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">{{ $editMode ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">Confirm</span>
                        <span class="fw-light"> Delete</span>
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-3">
                        <x-lucide-alert-triangle style="width: 48px; height: 48px;" class="text-danger mb-3" />
                        <p class="mb-1">Are you sure you want to delete</p>
                        @if (!empty($selectedIds))
                            <p class="fw-semibold text-danger">{{ count($selectedIds) }} selected workers</p>
                        @else
                            <strong class="fs-6">{{ $deleteItemName }}</strong>
                        @endif
                        <p class="text-muted small mt-2">This action cannot be undone.</p>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteConfirmed">
                        <x-lucide-trash-2 style="width: 16px; height: 16px;" class="me-1" /> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('show-modal', () => {
                var m = document.getElementById('workerModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).show();
            });
            document.addEventListener('hide-modal', () => {
                var m = document.getElementById('workerModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
            });
            document.addEventListener('show-delete-modal', () => {
                var m = document.getElementById('deleteModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).show();
            });
            document.addEventListener('hide-delete-modal', () => {
                var m = document.getElementById('deleteModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
            });
            document.addEventListener('show-import-modal', () => {
                var m = document.getElementById('importModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).show();
            });
            document.addEventListener('hide-import-modal', () => {
                var m = document.getElementById('importModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
            });
        </script>
    @endpush
</div>
