<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('invoices:mark-overdue', function () {
    $count = \App\Models\Invoice::where('status', 'pending')
        ->whereDate('due_at', '<', today())
        ->update(['status' => 'overdue']);

    $this->info("Marked {$count} invoice(s) as overdue.");
})->purpose('Flag pending invoices whose due date has passed as overdue');

\Illuminate\Support\Facades\Schedule::command('invoices:mark-overdue')->dailyAt('00:15');
