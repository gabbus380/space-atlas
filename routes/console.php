<?php

use App\Models\FileUnlock;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

// Retention promised in the privacy policy (3.6). Turn on the scheduler for the app in Laravel Cloud.
Schedule::call(fn () => FileUnlock::where('created_at', '<', now()->subMonths(13))->delete())->monthly()->name('prune-file-unlocks');
Schedule::call(fn () => TeamInvitation::whereNull('accepted_at')->where('expires_at', '<', now()->subDays(30))->delete())->weekly()->name('prune-invitations');
Schedule::command('auth:clear-resets')->daily();
Schedule::command('atlas:mail-briefing')->dailyAt('06:20')->timezone('UTC');
Schedule::command('atlas:mail-renewals')->dailyAt('14:00')->timezone('UTC');
Schedule::command('atlas:mail-newsletter')->monthlyOn(1, '15:00')->timezone('UTC');
