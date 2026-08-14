<div>
    <div class="page-header">
        <h3 class="fw-bold mb-3">My Profile</h3>
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
                <a href="#">Account</a>
            </li>
            <li class="separator">
                <x-lucide-chevron-right style="width: 12px; height: 12px;" />
            </li>
            <li class="nav-item">
                <a href="#">Profile</a>
            </li>
        </ul>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-profile">
                <div class="card-header" style="background-image: url('{{ asset('assets/img/blogpost.jpg') }}')">
                    <div class="profile-picture">
                        <div class="avatar" style="width: 120px; height: 120px;">
                            @if ($currentPhoto)
                                <img src="{{ Storage::url($currentPhoto) }}" alt="..." class="avatar-img rounded-circle" style="width: 120px; height: 120px; object-fit: cover;" />
                            @else
                                <img src="{{ asset('assets/img/profile.jpg') }}" alt="..." class="avatar-img rounded-circle" style="width: 120px; height: 120px; object-fit: cover;" />
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="user-profile text-center">
                        <div class="name fs-4 fw-bold">{{ $name }}</div>
                        <div class="job text-muted">{{ auth()->user()?->email ?? '' }}</div>
                        <div class="desc text-muted small mt-1">{{ auth()->user() ? 'Member since ' . auth()->user()->created_at->format('M Y') : '' }}</div>
                        <div class="mt-2">
                            @foreach (auth()->user()?->roles ?? [] as $role)
                                <span class="badge rounded-pill badge-primary me-1">{{ $role->name }}</span>
                            @endforeach
                        </div>
                        <button wire:click="$dispatch('show-edit-modal')" class="btn btn-primary rounded-3 mt-3">
                            <x-lucide-pencil style="width: 16px; height: 16px;" class="me-1" /> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <span class="fw-mediumbold">Edit</span>
                        <span class="fw-light"> Profile</span>
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;" />
                            @elseif ($currentPhoto)
                                <img src="{{ Storage::url($currentPhoto) }}" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;" />
                            @else
                                <img src="{{ asset('assets/img/profile.jpg') }}" class="rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;" />
                            @endif
                            <input type="file" wire:model="photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/jpg">
                            <small class="text-muted">JPG or PNG. Max 2MB.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Name</label>
                            <input wire:model="name" type="text" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input wire:model="email" type="email" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <x-lucide-save style="width: 16px; height: 16px;" class="me-1" /> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('show-edit-modal', () => {
            var m = document.getElementById('editProfileModal');
            if (m) bootstrap.Modal.getOrCreateInstance(m).show();
        });
        document.addEventListener('close-edit-modal', () => {
            var m = document.getElementById('editProfileModal');
            if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
        });
    </script>
    @endpush
</div>
