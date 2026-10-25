<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', ['email' => $request->query('email'), 'plan' => $request->query('plan')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:190', Rule::unique(User::class)],
            'organization' => ['nullable', 'string', 'max:160'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please accept the Terms of Service and the Privacy Policy.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'organization' => $data['organization'] ?? null,
            'password' => $data['password'],
            'marketing_opt_in' => $request->boolean('marketing'),
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        // an invitation link proves the inbox, so the new member joins right away
        if (str_contains((string) $request->session()->get('url.intended'), '/invite/')) {
            return redirect()->intended('/');
        }

        // any other intended page (checkout) stays in the session until the address is confirmed
        return redirect()->route('verification.notice');
    }
}
