<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monthly reminders generation
Schedule::command('reminders:generate-monthly')
    ->monthly()
    ->at('00:00')
    ->description('Generate monthly reminders for bills, customer dues, and credit terms');

// Schedule daily invoice due date notifications
Schedule::command('invoices:send-due-date-notifications')
    ->daily()
    ->at('09:00')
    ->description('Send email notifications for unpaid invoices with upcoming or past due dates');
