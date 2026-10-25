<?php

namespace App\Http\Controllers;

use App\Support\Billing;
use App\Support\Plans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $tier = Plans::tier($user);
        $personal = Billing::subscriptionFor($user, 'personal');
        $teams = Billing::subscriptionFor($user, 'teams');

        return view('account.show', [
            'user' => $user,
            'tier' => $tier,
            'personal' => $personal,
            'teamsSub' => $teams,
            'team' => $user->team,
            'credits' => $tier === 'free' ? Plans::credits($user) : null,
            'transactions' => $user->transactions()->orderByDesc('billed_at')->limit(24)->get(),
        ]);
    }

    public function profile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:160'],
        ]);
        $request->user()->forceFill($data + ['marketing_opt_in' => $request->boolean('marketing')])->save();

        return back()->with('status', 'Profile saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->forceFill(['password' => $data['password']])->save();

        return back()->with('status', 'Password changed.');
    }

    /** Everything the account holds, for the reader's own records (GDPR Articles 15 and 20). */
    public function export(Request $request): JsonResponse
    {
        $u = $request->user();
        $data = [
            'exported_at' => now()->toIso8601ZuluString(),
            'account' => $u->only(['name', 'email', 'organization', 'created_at', 'email_verified_at', 'marketing_opt_in', 'terms_accepted_at', 'watch_scope']),
            'plan' => Plans::tier($u),
            'watchlist' => $u->watchlist,
            'team' => $u->team ? ['name' => $u->team->name, 'role' => $u->team->owner_id === $u->id ? 'owner' : 'member', 'joined_at' => $u->team_joined_at] : null,
            'team_notes_written' => $u->team ? $u->team->notes()->where('updated_by', $u->id)->get(['iso3', 'text', 'updated_at']) : [],
            'country_files_opened' => $u->unlocks()->get(['iso3', 'month', 'created_at']),
            'subscriptions' => $u->subscriptions()->get(['type', 'status', 'created_at', 'ends_at']),
            'payments' => $u->transactions()->get(['invoice_number', 'status', 'total', 'tax', 'currency', 'billed_at']),
        ];

        return response()->json($data, 200, ['Content-Disposition' => 'attachment; filename="sovereign-space-atlas-account.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $u = $request->user();
        foreach (['personal', 'teams'] as $plan) {
            $s = Billing::subscriptionFor($u, $plan);
            if ($s && ! $s->canceled()) {
                return back()->withErrors(['password' => 'Cancel your '.ucfirst($plan).' plan first. You can delete the account once it is cancelled.']);
            }
        }
        if ($u->ownedTeam && $u->ownedTeam->members()->where('id', '!=', $u->id)->exists()) {
            return back()->withErrors(['password' => 'Your team still has members. Remove them first, or write to '.config('atlas.contact').' to hand the team over.']);
        }
        Auth::guard('web')->logout();
        $u->ownedTeam?->delete();
        $u->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account is deleted.');
    }
}
