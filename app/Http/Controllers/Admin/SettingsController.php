<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Connectors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.connectors.edit', [
            'sandbox' => Setting::has('paddle.sandbox') ? Setting::get('paddle.sandbox') === '1' : (bool) config('cashier.sandbox'),
            'hints' => collect(array_merge(Connectors::PADDLE, Connectors::AI))
                ->mapWithKeys(fn ($label, $key) => [$key => Setting::hint($key)]),
            'ai' => [
                'provider' => Setting::get('ai.provider', ''),
                'model' => Setting::get('ai.model', ''),
                'base_url' => Setting::get('ai.base_url', ''),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'paddle_sandbox' => ['nullable', 'boolean'],
            'paddle' => ['array'],
            'paddle.*' => ['nullable', 'string', 'max:500'],
            'ai' => ['array'],
            'ai.provider' => ['nullable', 'in:openai,anthropic,google'],
            'ai.model' => ['nullable', 'string', 'max:120'],
            'ai.base_url' => ['nullable', 'string', 'max:300'],
            'ai.api_key' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::put('paddle.sandbox', $request->boolean('paddle_sandbox') ? '1' : '0');
        foreach (array_keys(Connectors::PADDLE) as $key) {
            $field = str_replace('paddle.', '', $key);
            $value = trim((string) ($data['paddle'][$field] ?? ''));
            if ($value !== '') {
                Setting::put($key, $value);
            }
        }
        Setting::put('ai.provider', $data['ai']['provider'] ?? null);
        Setting::put('ai.model', trim((string) ($data['ai']['model'] ?? '')) ?: null);
        Setting::put('ai.base_url', trim((string) ($data['ai']['base_url'] ?? '')) ?: null);
        $aiKey = trim((string) ($data['ai']['api_key'] ?? ''));
        if ($aiKey !== '') {
            Setting::put('ai.api_key', $aiKey);
        }
        Connectors::apply();

        return back()->with('status', 'Connectors saved. Blank secret fields were left as they are.');
    }

    public function signIn(): View
    {
        $base = rtrim((string) config('app.url'), '/');
        $saved = [];
        foreach (Connectors::SIGNIN as $slug => $provider) {
            foreach ($provider['fields'] as $field => $meta) {
                $key = 'signin.'.$slug.'.'.$field;
                $saved[$slug][$field] = [
                    'value' => $meta[1] ? '' : (Setting::get($key) ?? ''),
                    'hint' => $meta[1] ? Setting::hint($key) : null,
                ];
            }
        }

        return view('admin.signin.edit', [
            'base' => $base,
            'saved' => $saved,
            'ready' => collect(Connectors::SIGNIN)->mapWithKeys(fn ($p, $slug) => [$slug => Connectors::signInReady($slug)]),
        ]);
    }

    public function saveSignIn(Request $request): RedirectResponse
    {
        $rules = ['providers' => ['array'], 'forget' => ['array']];
        foreach (Connectors::SIGNIN as $slug => $provider) {
            foreach ($provider['fields'] as $field => $meta) {
                $rules['providers.'.$slug.'.'.$field] = ['nullable', 'string', 'max:'.($field === 'private_key' ? 8000 : 500)];
            }
        }
        $data = $request->validate($rules);

        foreach (Connectors::SIGNIN as $slug => $provider) {
            if ($request->boolean('forget.'.$slug)) {
                foreach ($provider['fields'] as $field => $meta) {
                    Setting::put('signin.'.$slug.'.'.$field, null);
                }

                continue;
            }
            foreach ($provider['fields'] as $field => $meta) {
                $value = trim((string) ($data['providers'][$slug][$field] ?? ''));
                if ($meta[1] && $value === '') {
                    continue;
                }
                if ($field === 'tenant' && $value === '') {
                    $value = 'common';
                }
                Setting::put('signin.'.$slug.'.'.$field, $value !== '' ? $value : null);
            }
        }
        Connectors::apply();

        return back()->with('status', 'Sign-in settings saved. Blank secret fields were left as they are.');
    }
}
