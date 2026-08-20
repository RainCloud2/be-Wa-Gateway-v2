<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirect('/');
    }
}; ?>

<div>
    @if (session('status'))
        <div class="alert alert-success mb-4">{{ session('status') }}</div>
    @endif

    <div class="row justify-content-center align-items-center min-vh-100 mx-0 bg-light">
        <div class="col-11 col-sm-8 col-md-3 col-lg-3">
            <div class="card border-0 shadow-sm rounded-3">
                <!-- Header -->
                <div class="card-header bg-white border-0 text-center pt-3">
                    <img class="mt-3 mb-2" src="{{ asset('assets/img/tdp-dark.png') }}" alt="Logo" height="32" />
                    <p class="text-muted small mb-2">Sign in to your account</p>
                </div>

                <!-- Body -->
                <div class="card-body px-4 py-4">
                    <form wire:submit="login">

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label small text-muted">Email Address</label>
                            <input wire:model="form.email" id="email" type="email"
                                class="form-control form-control-lg @error('form.email') is-invalid @enderror"
                                name="email" required autofocus autocomplete="username"
                                placeholder="Enter your email" />
                            @error('form.email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label small text-muted">Password</label>
                            <input wire:model="form.password" id="password" type="password"
                                class="form-control form-control-lg @error('form.password') is-invalid @enderror"
                                name="password" required autocomplete="current-password"
                                placeholder="Enter your password" />
                            @error('form.password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                                Log In
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>

</div>
