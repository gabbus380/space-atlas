<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

/** Sign in with Apple (the Apple ID used for iCloud), Google, Microsoft or LinkedIn. */
class SocialController extends Controller
{
    public const PROVIDERS = [
        'apple' => 'Apple',
        'google' => 'Google',
        'microsoft' => 'Microsoft',
        'linkedin' => 'LinkedIn',
    ];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(isset(self::PROVIDERS[$provider]), 404);
        if (! $this->configured($provider)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Sign in with '.self::PROVIDERS[$provider].' is not connected yet.',
            ]);
        }

        return Socialite::driver($this->driver($provider))->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(isset(self::PROVIDERS[$provider]), 404);
        if (! $this->configured($provider)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Sign in with '.self::PROVIDERS[$provider].' is not connected yet.',
            ]);
        }

        try {
            $social = Socialite::driver($this->driver($provider))->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->withErrors(['email' => 'That sign-in expired. Try '.self::PROVIDERS[$provider].' again.']);
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['email' => self::PROVIDERS[$provider].' did not complete the sign-in.']);
        }

        $providerId = (string) $social->getId();
        $email = Str::lower(trim((string) $social->getEmail()));
        $user = User::query()->where('auth_provider', $provider)->where('auth_provider_id', $providerId)->first();
        if (! $user && $email !== '') {
            $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();
        }
        if (! $user && $email === '') {
            return redirect()->route('login')->withErrors([
                'email' => self::PROVIDERS[$provider].' did not share an email address. Remove Sovereign Space ATLAS from that account and try again.',
            ]);
        }

        if ($user === null) {
            $name = trim((string) $social->getName()) ?: Str::before($email, '@');
            $user = new User;
            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => null,
                'terms_accepted_at' => now(),
            ])->save();
        }
        if (! $user->auth_provider) {
            $user->forceFill(['auth_provider' => $provider, 'auth_provider_id' => $providerId])->save();
        }
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended('/');
    }

    /** Socialite's driver name, where it differs from the button. */
    private function driver(string $provider): string
    {
        return $provider === 'linkedin' ? 'linkedin-openid' : $provider;
    }

    private function configured(string $provider): bool
    {
        return filled(config('services.'.$this->driver($provider).'.client_id'));
    }
}
