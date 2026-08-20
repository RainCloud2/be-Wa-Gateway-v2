<div>
    <div class="bg-light min-vh-100 py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">

                    @if ($success)
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body p-5 text-center">
                                <div class="text-success mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" style="width: 56px; height: 56px;">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                </div>
                                <h4 class="fw-bold">Pendaftaran Berhasil</h4>
                                <p class="text-muted mb-4">
                                    Terima kasih! Data kamu sudah terdaftar sebagai pekerja.
                                    Silakan tunggu verifikasi oleh admin.
                                </p>
                                <a href="{{ route('register.worker') }}" class="btn btn-primary">
                                    Daftar Lagi
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white border-0 text-center pt-4">
                                <img src="{{ asset('assets/img/tdp-dark.png') }}" alt="Logo"
                                    height="32" />
                                <h5 class="fw-bold mt-3 mb-1">Registrasi Pekerja</h5>
                                <p class="text-muted small mb-2">Lengkapi data diri kamu di bawah ini</p>
                            </div>

                            <div class="card-body px-4 py-4">
                                <form wire:submit="save">

                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Ops ID <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="opsId" type="text"
                                                class="form-control @error('opsId') is-invalid @enderror"
                                                placeholder="Ops ID">
                                            @error('opsId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">NIK <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="nik" type="text"
                                                class="form-control @error('nik') is-invalid @enderror" maxlength="20"
                                                placeholder="NIK">
                                            @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Nama Lengkap <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="fullName" type="text"
                                                class="form-control @error('fullName') is-invalid @enderror"
                                                placeholder="Nama lengkap">
                                            @error('fullName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">No. HP <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="phoneNumber" type="text"
                                                class="form-control @error('phoneNumber') is-invalid @enderror"
                                                placeholder="Nomor HP">
                                            @error('phoneNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Bank <span
                                                    class="text-danger">*</span></label>
                                            <x-bank-select :banks="$banks" model="bankId" />
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Nomor Rekening <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="accountNumber" type="text"
                                                class="form-control @error('accountNumber') is-invalid @enderror"
                                                placeholder="Nomor rekening">
                                            @error('accountNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small text-muted mb-1">Alamat Domisili <span
                                                class="text-danger">*</span></label>
                                        <textarea wire:model="domicileAddress" rows="2"
                                            class="form-control @error('domicileAddress') is-invalid @enderror"
                                            placeholder="Alamat domisili"></textarea>
                                        @error('domicileAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <hr class="my-3">
                                    <h6 class="fw-bold mb-2">Kontak Darurat</h6>

                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Nomor Kontak <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="emergencyContactNumber" type="text"
                                                class="form-control @error('emergencyContactNumber') is-invalid @enderror"
                                                placeholder="Nomor kontak darurat">
                                            @error('emergencyContactNumber') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Nama Kontak <span
                                                    class="text-danger">*</span></label>
                                            <input wire:model="emergencyContactName" type="text"
                                                class="form-control @error('emergencyContactName') is-invalid @enderror"
                                                placeholder="Nama kontak darurat">
                                            @error('emergencyContactName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small text-muted mb-1">Hubungan <span
                                                class="text-danger">*</span></label>
                                        <input wire:model="emergencyContactRelation" type="text"
                                            class="form-control @error('emergencyContactRelation') is-invalid @enderror"
                                            placeholder="cth: Suami/Istri, Orang Tua">
                                        @error('emergencyContactRelation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <hr class="my-3">

                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Foto KTP <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" wire:model="ktpPhoto" class="form-control"
                                                accept="image/*" capture>
                                            @error('ktpPhoto') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if ($ktpPhoto)
                                                <img src="{{ $ktpPhoto->temporaryUrl() }}" class="img-fluid mt-2 rounded"
                                                    style="max-height: 120px;" />
                                            @endif
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label small text-muted mb-1">Foto Diri <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" wire:model="selfPhoto" class="form-control"
                                                accept="image/*" capture>
                                            @error('selfPhoto') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                            @if ($selfPhoto)
                                                <img src="{{ $selfPhoto->temporaryUrl() }}" class="img-fluid mt-2 rounded"
                                                    style="max-height: 120px;" />
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-grid mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                            Daftar
                                        </button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    @endif

                    <p class="text-center text-muted small mt-3 mb-0">
                        <a href="{{ route('login') }}" class="text-muted">Sudah punya akun? Masuk di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
