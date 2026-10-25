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
