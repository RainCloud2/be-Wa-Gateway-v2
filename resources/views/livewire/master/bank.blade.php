<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Bank Management</h3>
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
                <a href="#">Banks</a>
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
                                @can('bank.delete')
                                    <button wire:click="toggleSelectMode"
                                        class="btn btn-secondary rounded-3 btn-sm">Cancel</button>
                                    <button wire:click="confirmBulkDelete" class="btn btn-danger rounded-3 btn-sm"
                                        x-bind:disabled="$wire.selectedIds.length === 0">
                                        <x-lucide-trash-2 style="width: 14px; height: 14px;" class="me-1" /> Delete
                                        Selected
                                    </button>
                                @endcan
                            @else
                                @can('bank.create')
                                    <button wire:click="create" class="btn btn-primary rounded-3 btn-sm">
                                        <x-lucide-plus style="width: 14px; height: 14px;" class="me-1" /> Add Bank
                                    </button>
                                @endcan
                                @can('bank.delete')
                                    <button wire:click="toggleSelectMode"
                                        class="btn btn-outline-secondary rounded-3 btn-sm">
                                        <x-lucide-check-square style="width: 14px; height: 14px;" class="me-1" /> Select
                                    </button>
                                @endcan
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            @can('bank.import')
                                <button wire:click="$dispatch('show-import-modal')"
                                    class="btn btn-label-info rounded-3 btn-sm">
                                    <x-lucide-upload style="width: 14px; height: 14px;" class="me-1" /> Import
                                </button>
                            @endcan
                            @can('bank.export')
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
                                                x-bind:checked="$wire.selectedIds.length === {{ $banks->total() }}">
                                        </th>
                                    @endif
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Initial</th>
                                    <th>Description</th>
                                    @can('bank.delete' || 'bank.update')
                                        <th>Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($banks as $bank)
                                    <tr>
                                        @if ($selectMode)
                                            <td><input type="checkbox" wire:model="selectedIds"
                                                    value="{{ $bank->id }}" wire:key="bank-{{ $bank->id }}">
                                            </td>
                                        @endif
                                        <td>{{ $loop->iteration + ($banks->currentPage() - 1) * $banks->perPage() }}
                                        </td>
                                        <td>{{ $bank->name }}</td>
                                        <td><span class="badge rounded-pill badge-primary">{{ $bank->initial }}</span>
                                        </td>
                                        <td>{{ $bank->description }}</td>
                                        @can('bank.delete' || 'bank.update')
                                            <td>
                                                <button wire:click="edit({{ $bank->id }})"
                                                    class="btn btn-link btn-primary btn-xs p-1">
                                                    <x-lucide-pencil style="width: 16px; height: 16px;" />
                                                </button>
                                                <button
                                                    wire:click="confirmDelete({{ $bank->id }}, '{{ $bank->name }}')"
                                                    class="btn btn-link btn-danger btn-xs p-1">
                                                    <x-lucide-trash-2 style="width: 16px; height: 16px;" />
                                                </button>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No banks found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :items="$banks" />
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
                        <span class="fw-light"> Banks</span>
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
    <div class="modal fade" id="bankModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">{{ $editMode ? 'Edit' : 'New' }}</span>
                        <span class="fw-light"> Bank</span>
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <form wire:submit.prevent="{{ $editMode ? 'update' : 'store' }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Name</label>
                            <input wire:model="name" type="text" class="form-control">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Initial</label>
                            <input wire:model="initial" type="text" class="form-control" placeholder="e.g. BCA">
                            @error('initial') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea wire:model="description" class="form-control" rows="3"></textarea>
                            @error('description') <small class="text-danger">{{ $message }}</small> @enderror
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
                            <p class="fw-semibold text-danger">{{ count($selectedIds) }} selected banks</p>
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
                var m = document.getElementById('bankModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).show();
            });
            document.addEventListener('hide-modal', () => {
                var m = document.getElementById('bankModal');
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
