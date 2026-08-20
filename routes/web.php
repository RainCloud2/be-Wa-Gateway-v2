<?php

use App\Livewire\Account\Permission;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Role;
use App\Livewire\Account\User;
use App\Livewire\Actions\Logout;
use App\Livewire\Cms\Dashboard;
use App\Livewire\Master\Bank;
use App\Livewire\Master\RegisterWorker;
use App\Livewire\Master\Worker;
use App\Livewire\Master\WorkerDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/register-worker', RegisterWorker::class)
    ->name('register.worker');

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

Route::middleware(['auth'])->prefix('master')->name('master.')->group(function () {

    Route::get('/banks', Bank::class)
        ->middleware('permission:bank.read')
        ->name('banks');

    Route::get('/workers', Worker::class)
        ->middleware('permission:worker.read')
        ->name('workers');

    Route::get('/workers/{worker}', WorkerDetail::class)
        ->middleware('permission:worker.read')
        ->name('workers.show');
});

require __DIR__.'/auth.php';
