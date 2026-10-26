<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/** Paddle and AI credentials saved from the admin connectors page. */
class Connectors
{
    public const PADDLE = [
        'paddle.client_side_token' => 'Client-side token',
        'paddle.api_key' => 'API key',
        'paddle.webhook_secret' => 'Webhook secret',
        'paddle.price_personal_year' => 'Personal yearly price',
        'paddle.price_personal_month' => 'Personal monthly price',
        'paddle.price_teams_year' => 'Teams yearly price',
        'paddle.price_teams_month' => 'Teams monthly price',
    ];

    public const AI = [
        'ai.provider' => 'Provider',
        'ai.model' => 'Model',
        'ai.base_url' => 'Base URL',
        'ai.api_key' => 'API key',
    ];

    public static function apply(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $map = [
            'paddle.api_key' => 'cashier.api_key',
            'paddle.client_side_token' => 'cashier.client_side_token',
            'paddle.webhook_secret' => 'cashier.webhook_secret',
        ];
        foreach ($map as $key => $config) {
            $value = Setting::get($key);
            if (filled($value)) {
                Config::set($config, $value);
            }
        }
        if (Setting::has('paddle.sandbox')) {
            Config::set('cashier.sandbox', Setting::get('paddle.sandbox') === '1');
        }

        $prices = [
            'paddle.price_personal_year' => 'atlas.paddle_prices.personal.year',
            'paddle.price_personal_month' => 'atlas.paddle_prices.personal.month',
            'paddle.price_teams_year' => 'atlas.paddle_prices.teams.year',
            'paddle.price_teams_month' => 'atlas.paddle_prices.teams.month',
        ];
        foreach ($prices as $key => $config) {
            $value = Setting::get($key);
            if (filled($value)) {
                Config::set($config, $value);
            }
        }

        self::applySignIn();
        self::applyAi();
    }

    /** The saved AI connector, or the Anthropic key set on the server. */
    public static function applyAi(): void
    {
        $key = Setting::get('ai.api_key');
        if (! filled($key)) {
            $key = config('services.anthropic.key');
            if (filled($key)) {
                Setting::put('ai.api_key', $key);
                if (! filled(Setting::get('ai.provider'))) {
                    Setting::put('ai.provider', 'anthropic');
                }
            }
        }
        if (filled($key)) {
            Config::set('services.anthropic.key', $key);
        }
        foreach ([
            'ai.provider' => 'provider',
            'ai.model' => 'model',
            'ai.base_url' => 'url',
            'ai.workspace' => 'workspace',
        ] as $setting => $name) {
            $value = Setting::get($setting);
            if (filled($value)) {
                Config::set('services.anthropic.'.$name, $value);
            }
        }
    }

    /** Fields an admin pastes for each sign-in provider. Secret fields stay blank to keep the saved value. */
    public const SIGNIN = [
        'apple' => [
            'name' => 'Apple',
            'driver' => 'apple',
            'where' => 'Apple Developer → Certificates, Identifiers & Profiles. Create a Services ID, enable Sign in with Apple, and register the redirect URL below. Create a Key with Sign in with Apple and download the .p8 file once.',
            'fields' => [
                'client_id' => ['Services ID', false, 'The Services ID you create for Sign in with Apple. It often looks like a reverse domain, for example space.emmaconsulting.atlas. Apple uses it as the client id.'],
                'team_id' => ['Team ID', false, 'The 10-character Team ID at the top of your Apple Developer account. It is required to sign the short-lived secret Apple expects.'],
                'key_id' => ['Key ID', false, 'The identifier of the key you created with Sign in with Apple enabled. It names which .p8 file signs the secret.'],
                'private_key' => ['Private key (.p8 contents)', true, 'The full text of the .p8 file Apple lets you download once, including the BEGIN and END lines. Leave it blank to keep the key already saved.'],
            ],
        ],
        'google' => [
            'name' => 'Google',
            'driver' => 'google',
            'where' => 'Google Cloud Console → APIs & Services → Credentials → OAuth client ID, type Web application. Add the redirect URL below.',
            'fields' => [
                'client_id' => ['Client ID', false, 'The OAuth client id from a Web application credential. It ends in .apps.googleusercontent.com.'],
                'client_secret' => ['Client secret', true, 'The client secret from that same Google credential. It proves the sign-in request came from ATLAS. Leave it blank to keep the saved secret.'],
            ],
        ],
        'microsoft' => [
            'name' => 'Microsoft',
            'driver' => 'microsoft',
            'where' => 'Microsoft Entra admin center → App registrations → New registration. Under Authentication, add the redirect URL as a Web platform. Create a client secret under Certificates & secrets.',
            'fields' => [
                'client_id' => ['Application (client) ID', false, 'The application id of the Entra app registration. It is a long hexadecimal identifier.'],
                'client_secret' => ['Client secret', true, 'The secret value from Certificates & secrets, not the secret id. Leave it blank to keep the saved secret.'],
                'tenant' => ['Directory (tenant)', false, 'Use common to allow both work and personal Microsoft accounts. Paste a tenant id only if sign-in should be limited to one directory.'],
            ],
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'driver' => 'linkedin-openid',
            'where' => 'LinkedIn Developer Portal → Create app → Auth. Add the redirect URL and request the Sign In with LinkedIn using OpenID Connect product.',
            'fields' => [
                'client_id' => ['Client ID', false, 'The client id on the Auth tab of your LinkedIn app, after Sign In with LinkedIn using OpenID Connect is approved.'],
                'client_secret' => ['Client secret', true, 'The primary client secret on that same Auth tab. Leave it blank to keep the saved secret.'],
            ],
        ],
    ];

    public static function applySignIn(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }
        $base = rtrim((string) config('app.url'), '/');
        foreach (self::SIGNIN as $slug => $provider) {
            $driver = $provider['driver'];
            Config::set('services.'.$driver.'.redirect', $base.'/login/'.$slug.'/callback');
            foreach ($provider['fields'] as $field => $meta) {
                $value = Setting::get('signin.'.$slug.'.'.$field);
                if (filled($value)) {
                    Config::set('services.'.$driver.'.'.$field, $value);
                }
            }
        }
    }

    public static function signInReady(string $slug): bool
    {
        $provider = self::SIGNIN[$slug] ?? null;
        if (! $provider) {
            return false;
        }
        $driver = $provider['driver'];
        if (! filled(config('services.'.$driver.'.client_id'))) {
            return false;
        }
        if ($slug === 'apple') {
            return filled(config('services.apple.team_id'))
                && filled(config('services.apple.key_id'))
                && filled(config('services.apple.private_key'));
        }

        return filled(config('services.'.$driver.'.client_secret'));
    }

    public static function ai(): array
    {
        return [
            'provider' => Setting::get('ai.provider'),
            'model' => Setting::get('ai.model'),
            'base_url' => Setting::get('ai.base_url'),
            'configured' => filled(Setting::get('ai.api_key')),
        ];
    }
}
