<?php

namespace App\Providers;

use App\Listeners\SendBillingMail;
use App\Listeners\SendWelcomeMail;
use App\Mail\Letters;
use App\Support\AtlasData;
use App\Support\Connectors;
use App\Support\Staff;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Paddle\Events\WebhookHandled;

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

        Event::listen(Verified::class, SendWelcomeMail::class);
        Event::listen(WebhookHandled::class, SendBillingMail::class);

        // App\Listeners\SyncSubscriptionMeta is found by event discovery.

        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => Letters::verify($notifiable, $url)->message());

        ResetPassword::toMailUsing(fn (object $notifiable, string $token) => Letters::resetPassword(
            $notifiable,
            route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]),
        )->message());
    }
}
