<?php

use App\Http\Controllers\Api\Wa\IncomingController;
use App\Http\Controllers\Api\Wa\StatusController;
use App\Http\Controllers\Api\Wa\WhitelistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['gateway.token', 'throttle:wa'])
    ->prefix('wa')
    ->name('api.wa.')
    ->group(function () {
        Route::post('/incoming', [IncomingController::class, 'store'])->name('incoming');
        Route::post('/status', [StatusController::class, 'store'])->name('status');
        Route::get('/whitelist', [WhitelistController::class, 'index'])->name('whitelist');
    });
