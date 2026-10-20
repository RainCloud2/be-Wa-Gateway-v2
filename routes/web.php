<?php

use App\Livewire\Account\Permission;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Role;
use App\Livewire\Account\User;
use App\Livewire\Actions\Logout;
use App\Livewire\Cms\Dashboard;
use App\Livewire\Wa\Employees;
use App\Livewire\Wa\Settings;
use App\Livewire\Wa\Simulator;
use App\Livewire\Wa\TicketDetail;
use App\Livewire\Wa\Tickets;
use App\Livewire\Wa\WhatsAppStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::post('/logout', function (Request $request) {
    app(Logout::class)();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware(['auth'])->prefix('account')->name('account.')->group(function () {

    Route::get('/profile', Profile::class)
        ->name('profile');

    Route::get('/users', User::class)
        ->middleware('permission:user.read')
        ->name('users');

    Route::get('/roles', Role::class)
        ->middleware('permission:role.read')
        ->name('roles');

    Route::get('/permissions', Permission::class)
        ->middleware('permission:permission.read')
        ->name('permissions');
});

Route::middleware(['auth'])->prefix('wa')->name('wa.')->group(function () {

    Route::get('/tickets', Tickets::class)
        ->middleware('permission:ticket.read')
        ->name('tickets.index');

    Route::get('/tickets/{ticket}', TicketDetail::class)
        ->middleware('permission:ticket.read')
        ->name('tickets.show');

    Route::get('/employees', Employees::class)
        ->middleware('permission:employee.read')
        ->name('employees');

    Route::get('/settings', Settings::class)
        ->middleware('permission:setting.read')
        ->name('settings');

    Route::get('/whatsapp', WhatsAppStatus::class)
        ->middleware('permission:wa.read')
        ->name('whatsapp');

    Route::get('/simulator', Simulator::class)
        ->middleware('permission:wa.update')
        ->name('simulator');
});

require __DIR__.'/auth.php';
