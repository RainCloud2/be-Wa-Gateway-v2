<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// WhatsApp chatbot maintenance.
Schedule::command('wa:auto-close-resolved')->hourly();
Schedule::command('wa:remind-unassigned')->everyFiveMinutes();
