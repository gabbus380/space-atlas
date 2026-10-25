<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Team;
use App\Models\User;
use App\Support\Kpi;
use App\Support\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function show(): View
    {
        $users = User::with('team')->latest()->limit(40)->get();

        return view('admin.show', [
            'kpi' => Kpi::snapshot(),
            'users' => $users->map(fn ($u) => ['u' => $u, 'tier' => Plans::tier($u)]),
            'teams' => Team::with('owner')->withCount('members')->latest()->limit(40)->get(),
            'keys' => ApiKey::latest()->get(),
        ]);
    }

    private function userByEmail(string $email): ?User
    {
        return User::whereRaw('lower(email) = ?', [Str::lower($email)])->first();
    }

    /** Complimentary Personal access, for example a journalist, a partner or a trial. */
    public function grant(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'days' => ['required', 'integer', 'min:1', 'max:730']]);
        $u = $this->userByEmail($data['email']);
        if (! $u) {
            return back()->withErrors(['email' => 'No account uses that address. Ask them to create a free account first.']);
        }
        $u->forceFill(['plan_override' => 'personal', 'override_until' => today()->addDays($data['days'])])->save();

        return back()->with('status', $u->email.' has Personal until '.$u->override_until->format('F j, Y').'.');
    }

    /** A Teams pilot, or a team that pays by invoice outside Paddle. */
    public function teamGrant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'], 'name' => ['required', 'string', 'max:120'],
            'seats' => ['required', 'integer', 'min:1', 'max:'.config('atlas.teams_max_seats')], 'days' => ['required', 'integer', 'min:1', 'max:800'],
        ]);
        $u = $this->userByEmail($data['email']);
        if (! $u) {
            return back()->withErrors(['email' => 'No account uses that address. The team owner creates a free account first.']);
        }
        if ($u->team_id && $u->team->owner_id !== $u->id) {
            return back()->withErrors(['email' => $u->email.' is a member of '.$u->team->name.'.']);
        }
        $team = $u->ownedTeam ?: Team::create(['name' => $data['name'], 'owner_id' => $u->id]);
        $team->forceFill(['name' => $data['name'], 'override_until' => today()->addDays($data['days']), 'override_seats' => $data['seats']])->save();
        $u->forceFill(['team_id' => $team->id, 'team_joined_at' => $u->team_joined_at ?? now()])->save();

        return back()->with('status', $team->name.' has Teams with '.$data['seats'].' seats until '.$team->override_until->format('F j, Y').'. The owner invites the team from their account.');
    }

    public function revoke(Request $request): RedirectResponse
    {
        $u = $this->userByEmail($request->validate(['email' => ['required', 'email']])['email']);
        abort_unless($u, 404);
        $u->forceFill(['plan_override' => null, 'override_until' => null])->save();
        $u->ownedTeam?->forceFill(['override_until' => null, 'override_seats' => null])->save();

        return back()->with('status', 'Complimentary access removed for '.$u->email.'.');
    }

    public function issueKey(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'contact_email' => ['nullable', 'email'],
            'daily_quota' => ['required', 'integer', 'min:100', 'max:1000000'], 'max_users' => ['required', 'integer', 'min:1', 'max:10000'],
            'expires_on' => ['nullable', 'date', 'after:today'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        [$key, $plain] = ApiKey::issue($data);

        return back()->with('status', 'API key for '.$key->name.' created. Copy it now, it is shown only once.')->with('plain_key', $plain);
    }

    public function toggleKey(ApiKey $key): RedirectResponse
    {
        $key->forceFill(['active' => ! $key->active])->save();

        return back()->with('status', $key->name.' is '.($key->active ? 'active' : 'switched off').'.');
    }
}
