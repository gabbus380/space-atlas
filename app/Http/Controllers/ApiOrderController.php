<?php

namespace App\Http\Controllers;

use App\Models\ApiRequest;
use App\Models\User;
use App\Support\Plans;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** The public API Order Form. Guests get a free account; signed-in readers keep theirs. */
class ApiOrderController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();

        return view('api-order', [
            'user' => $user,
            'tier' => Plans::tier($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $guest = $request->user() === null;
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['required', 'string', 'max:160'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'people' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'message' => ['required', 'string', 'max:2000'],
        ];
        if ($guest) {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:190', Rule::unique(User::class)];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
            $rules['terms'] = ['accepted'];
        }
        $data = $request->validate($rules, [
            'terms.accepted' => 'Please accept the Terms of Service and the Privacy Policy.',
            'email.unique' => 'That email already has an account. Sign in, then send the request.',
        ]);

        if ($guest) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'organization' => $data['organization'],
                'password' => $data['password'],
                'terms_accepted_at' => now(),
            ]);
            event(new Registered($user));
            Auth::login($user);
            $request->session()->regenerate();
            $tier = 'free';
            $created = true;
        } else {
            $user = $request->user();
            $tier = Plans::tier($user);
            $created = false;
            if ($data['organization'] !== $user->organization) {
                $user->forceFill(['organization' => $data['organization']])->save();
            }
        }

        ApiRequest::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'organization' => $data['organization'],
            'job_title' => $data['job_title'] ?? $user->job_title,
            'account_tier' => $tier,
            'created_account' => $created,
            'people' => $data['people'] ?? null,
            'message' => $data['message'],
        ]);

        return redirect()->route('api-order.done');
    }

    public function done(): View
    {
        return view('api-order-done');
    }
}
