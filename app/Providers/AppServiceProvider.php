<?php

namespace App\Providers;

use App\Support\AtlasData;
use App\Support\Connectors;
use App\Support\Staff;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\View;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(AtlasData::class);
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => Password::min(10)->letters()->numbers());
        try {
            Connectors::apply();
        } catch (\Throwable) {
            // The settings table is created by a migration. Boot still has to work before that.
        }
        View::composer('admin.*', fn ($view) => $view->with('adminNav', Staff::navigation(auth()->user())));

        // App\Listeners\SyncSubscriptionMeta is found by event discovery.

        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject('Confirm your email for Sovereign Space ATLAS')
            ->greeting('Hello '.($notifiable->firstName() ?? '').',')
            ->line('Confirm this address to open full country files and to subscribe.')
            ->action('Confirm email address', $url)
            ->line('If you did not create an account, you can ignore this email.'));

        ResetPassword::toMailUsing(fn (object $notifiable, string $token) => (new MailMessage)
            ->subject('Reset your Sovereign Space ATLAS password')
            ->line('Someone asked to reset the password for this account.')
            ->action('Choose a new password', url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false)))
            ->line('The link works for 60 minutes. If it was not you, no change is made.'));
    }
}
