<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">Whitelist</h3>
        <ul class="breadcrumbs mb-3">
            <li class="nav-home">
                <a href="{{ route('dashboard') }}">
                    <x-lucide-home style="width: 14px; height: 14px;" />
                </a>
            </li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">WhatsApp</a></li>
            <li class="separator"><x-lucide-chevron-right style="width: 12px; height: 12px;" /></li>
            <li class="nav-item"><a href="#">Whitelist</a></li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <h4 class="card-title mb-0">Daftar Nomor Whitelist</h4>
                        @can('employee.create')
                            <button wire:click="create" class="btn btn-primary rounded-3 btn-sm">
                                <x-lucide-plus style="width: 14px; height: 14px;" class="me-1" /> Tambah Whitelist
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">Tampilkan</small>
                            <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 65px;">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <small class="text-muted">data</small>
                        </div>
                        <div class="input-group input-group-sm border rounded input-group-search" style="width: 240px;">
                            <span class="input-group-text bg-transparent border-0 pe-0">
                                <x-lucide-search style="width: 14px; height: 14px;" class="text-muted" />
                            </span>
                            <input wire:model.live.debounce.300ms="search" type="text"
                                class="form-control border-0 ps-1 shadow-none" placeholder="Cari nama / nomor...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="display table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Nomor WhatsApp</th>
                                    <th>Departemen</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employees as $employee)
                                    <tr wire:key="employee-{{ $employee->id }}">
                                        <td>{{ $loop->iteration + ($employees->currentPage() - 1) * $employees->perPage() }}</td>
                                        <td>{{ $employee->name }}</td>
                                        <td><span class="font-monospace">{{ $employee->phone }}</span></td>
                                        <td>{{ $employee->department ?: '-' }}</td>
                                        <td>
                                            @can('employee.update')
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        id="employee-active-{{ $employee->id }}"
                                                        wire:click="toggleActive({{ $employee->id }})"
                                                        wire:loading.attr="disabled"
                                                        @checked($employee->is_active)>
                                                    <label class="form-check-label small text-muted"
                                                        for="employee-active-{{ $employee->id }}">
                                                        {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                                                    </label>
                                                </div>
                                            @else
                                                @if ($employee->is_active)
                                                    <span class="badge badge-success">Aktif</span>
                                                @else
                                                    <span class="badge badge-danger">Nonaktif</span>
                                                @endif
                                            @endcan
                                        </td>
                                        <td>
                                            @can('employee.update')
                                                <button wire:click="edit({{ $employee->id }})"
                                                    class="btn btn-link btn-primary btn-xs p-1">
                                                    <x-lucide-pencil style="width: 16px; height: 16px;" />
                                                </button>
                                            @endcan
                                            @can('employee.delete')
                                                <button wire:click="confirmDelete({{ $employee->id }}, '{{ $employee->name }}')"
                                                    class="btn btn-link btn-danger btn-xs p-1">
                                                    <x-lucide-trash-2 style="width: 16px; height: 16px;" />
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Belum ada data whitelist.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :items="$employees" />
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="employeeModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">{{ $editMode ? 'Ubah' : 'Tambah' }}</span>
                        <span class="fw-light"> Whitelist</span>
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <form wire:submit.prevent="{{ $editMode ? 'update' : 'store' }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama</label>
                            <input wire:model="name" type="text" class="form-control">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor WhatsApp</label>
                            <input wire:model="phone" type="text" class="form-control" placeholder="628123456789">
                            <small class="text-muted">Gunakan format 628xxx tanpa tanda + atau spasi.</small>
                            @error('phone') <small class="text-danger d-block">{{ $message }}</small> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Departemen</label>
                            <input wire:model="department" type="text" class="form-control">
                            @error('department') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="form-check form-switch">
                            <input wire:model="is_active" class="form-check-input" type="checkbox" role="switch" id="employeeActive">
                            <label class="form-check-label" for="employeeActive">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">{{ $editMode ? 'Simpan' : 'Tambah' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="employeeDeleteModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><span class="fw-mediumbold">Hapus</span> <span class="fw-light">Whitelist</span></h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center py-3">
                    <x-lucide-alert-triangle style="width: 48px; height: 48px;" class="text-danger mb-3" />
                    <p class="mb-1">Yakin ingin menghapus</p>
                    <strong class="fs-6">{{ $deleteItemName }}</strong>?
                    <p class="text-muted small mt-2 mb-0">Nomor akan dihapus dari whitelist. Riwayat tiket dan chat tetap tersimpan.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteConfirmed">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('show-modal', () => {
                var m = document.getElementById('employeeModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).show();
            });
            document.addEventListener('hide-modal', () => {
                var m = document.getElementById('employeeModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
            });
            document.addEventListener('show-delete-modal', () => {
                var m = document.getElementById('employeeDeleteModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).show();
            });
            document.addEventListener('hide-delete-modal', () => {
                var m = document.getElementById('employeeDeleteModal');
                if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
            });
        </script>
    @endpush
</div>
