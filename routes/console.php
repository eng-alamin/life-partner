<?php

use App\Models\OtpCode;
use App\Models\SmsMessage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove expired one-time codes (older than one day) and old SMS records.
Schedule::command('model:prune', ['--model' => [OtpCode::class, SmsMessage::class]])->daily();

// Close interest requests whose time has run out.
Schedule::command('interests:expire')->hourly()->withoutOverlapping();

// Mark membership periods that have ended as expired (and tell the member once).
Schedule::command('subscriptions:expire')->hourly()->withoutOverlapping();

// Tell members whose paid period ends soon. Once a day, in the morning.
Schedule::command('subscriptions:remind')->dailyAt('10:00')->withoutOverlapping();

// Ask bKash about API payments whose result was unclear. Before the stale clean-up can touch them.
Schedule::command('bkash:reconcile')->everyFiveMinutes()->withoutOverlapping();

// Cancel checkouts that were started but never submitted.
Schedule::command('payments:cancel-stale')->hourly()->withoutOverlapping();

// Delete notifications that were read long ago.
Schedule::command('notifications:prune')->daily();

// Purge accounts whose 14-day deletion wait is over. Night time, one run at a time.
Schedule::command('accounts:purge-deleted')->dailyAt('03:00')->withoutOverlapping();

// Close identity requests that waited too long and delete leftover identity pictures.
Schedule::command('identity:cleanup')->dailyAt('03:30')->withoutOverlapping();

// Delete contact form messages older than 180 days. Night time, one run at a time.
Schedule::command('contact:purge-old')->dailyAt('04:00')->withoutOverlapping();
