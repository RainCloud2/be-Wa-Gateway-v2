<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Cms\Dashboard;

Route::get('/', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    app(\App\Livewire\Actions\Logout::class)();
    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware(['auth'])->prefix('account')->name('account.')->group(function () {

    Route::get('/profile', \App\Livewire\Account\Profile::class)
        ->name('profile');

    Route::get('/users', \App\Livewire\Account\User::class)
        ->middleware('permission:user.read')
        ->name('users');

    Route::get('/roles', \App\Livewire\Account\Role::class)
        ->middleware('permission:role.read')
        ->name('roles');

    Route::get('/permissions', \App\Livewire\Account\Permission::class)
        ->middleware('permission:permission.read')
        ->name('permissions');
});

require __DIR__.'/auth.php';
