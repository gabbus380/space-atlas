<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Coupon;
use App\Support\Billing;
use App\Support\OrganizationDirectory;
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
            'apiKeys' => $user->apiKeys(),
        ]);
    }

    public function profile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:160'],
            'organization_id' => ['nullable', 'string', 'max:160'],
            'job_title' => ['nullable', 'string', 'max:160'],
        ]);
        $user = $request->user();
        $name = trim((string) ($data['organization'] ?? ''));
        $claim = null;

        if ($request->boolean('keep_unmatched')) {
            $claim = null;
        } elseif ($request->filled('organization_id')) {
            $claim = OrganizationDirectory::find($data['organization_id']);
            if (! $claim) {
                return back()->withErrors(['organization' => 'That organization is not in the directory.'])->withInput();
            }
        } elseif ($name !== '' && ! $request->boolean('keep_unmatched')) {
            $matches = OrganizationDirectory::search($name);
            $exact = array_values(array_filter($matches, fn ($org) => OrganizationDirectory::normalize($org['name'] ?? '') === OrganizationDirectory::normalize($name)));
            if (count($exact) === 1) {
                $claim = $exact[0];
            } elseif (count($matches) > 1) {
                return back()->with('org_matches', $matches)->withInput();
            }
        }

        if ($claim) {
            $errors = OrganizationDirectory::claimErrors($claim, $user, $data['job_title'] ?? null);
            if ($errors !== []) {
                return back()->withErrors($errors)->with('org_matches', [$claim])->withInput();
            }
        }

        $user->forceFill([
            'name' => $data['name'],
            'organization' => $claim['name'] ?? ($name !== '' ? $name : null),
            'organization_id' => $claim['id'] ?? null,
            'job_title' => $claim ? trim((string) ($data['job_title'] ?? '')) : null,
            'show_on_organization' => $claim ? $request->boolean('show_on_organization') : false,
            'marketing_opt_in' => $request->boolean('marketing'),
            'briefing_opt_out' => ! $request->boolean('briefing'),
        ])->save();

        $status = $claim
            ? 'Profile saved. '.$claim['name'].' is linked to this account.'
            : 'Profile saved.';

        return back()->with('status', $status);
    }

    public function coupon(Request $request): RedirectResponse
    {
        $code = strtoupper(trim((string) $request->validate(['code' => ['required', 'string', 'max:40']])['code']));
        $coupon = Coupon::query()->where('code', $code)->first();
        if (! $coupon) {
            return back()->withErrors(['code' => 'No coupon uses that code.']);
        }
        $coupon->redeem($request->user());

        return back()->with('status', $coupon->label().' is applied to your account.');
    }

    public function apiDocs(Request $request): View
    {
        $keys = $request->user()->apiKeys();
        abort_if($keys->isEmpty(), 404);

        return view('account.api', [
            'keys' => $keys,
            'base' => rtrim(config('app.url'), '/').'/v1',
        ]);
    }

    public function refreshApiKey(Request $request, ApiKey $key): RedirectResponse
    {
        abort_unless($request->user()->apiKeys()->contains('id', $key->id), 404);
        $plain = $key->rotate();

        return redirect()->route('account.api')->with('status', 'Your new API key is below. The previous one no longer works.')->with('plain_key', $plain);
    }

    public function password(Request $request): RedirectResponse
    {
        $rules = ['password' => ['required', 'confirmed', Password::defaults()]];
        if ($request->user()->password) {
            $rules['current_password'] = ['required', 'current_password'];
        }
        $data = $request->validate($rules);
        $request->user()->forceFill(['password' => $data['password']])->save();

        return back()->with('status', 'Password changed.');
    }

    /** Everything the account holds, for the reader's own records (GDPR Articles 15 and 20). */
    public function export(Request $request): JsonResponse
    {
        $u = $request->user();
        $data = [
            'exported_at' => now()->toIso8601ZuluString(),
            'account' => $u->only(['name', 'email', 'organization', 'created_at', 'email_verified_at', 'marketing_opt_in', 'briefing_opt_out', 'terms_accepted_at', 'watch_scope']),
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
        if ($request->user()->password) {
            $request->validate(['password' => ['required', 'current_password']]);
        }
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
