<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvite;
use App\Support\Billing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $team = $user->team;
        if (! $team) {
            return redirect()->route('subscribe.teams');
        }
        $owner = $team->owner_id === $user->id;
        $sub = $team->subscription();

        return view('team.show', [
            'team' => $team,
            'owner' => $owner,
            'members' => $team->members()->get(),
            'invitations' => $owner ? $team->pendingInvitations()->latest()->get() : collect(),
            'seats' => $team->seats(),
            'used' => $team->seatsUsed(),
            'active' => $team->isActive(),
            'subscription' => $sub,
            'interval' => $sub ? Billing::interval($sub) : null,
        ]);
    }

    private function ownedTeam(Request $request)
    {
        $team = $request->user()->ownedTeam;
        abort_unless($team, 403);

        return $team;
    }

    public function invite(Request $request): RedirectResponse
    {
        $team = $this->ownedTeam($request);
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);
        $email = Str::lower($data['email']);
        if (! $team->isActive()) {
            return back()->withErrors(['email' => 'Invitations open once the team plan is active.']);
        }
        if ($team->members()->whereRaw('lower(email) = ?', [$email])->exists()) {
            return back()->withErrors(['email' => 'That person is already on the team.']);
        }
        $open = $team->pendingInvitations()->whereRaw('lower(email) = ?', [$email])->first();
        if (! $open && $team->seatsUsed() >= $team->seats()) {
            return back()->withErrors(['email' => 'Every seat is taken. Add a seat first.']);
        }
        $token = Str::random(48);
        if ($open) {
            $open->forceFill(['token_hash' => TeamInvitation::hashToken($token), 'expires_at' => now()->addDays(14)])->save();
            $inv = $open;
        } else {
            $inv = TeamInvitation::create([
                'team_id' => $team->id, 'email' => $email, 'token_hash' => TeamInvitation::hashToken($token),
                'invited_by' => $request->user()->id, 'expires_at' => now()->addDays(14),
            ]);
        }
        Notification::route('mail', $email)->notify(new TeamInvite($inv, $token, $request->user()));

        return back()->with('status', 'Invitation sent to '.$email.'. The link works for 14 days.');
    }

    public function revoke(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        $team = $this->ownedTeam($request);
        abort_unless($invitation->team_id === $team->id, 404);
        $invitation->delete();

        return back()->with('status', 'Invitation withdrawn.');
    }

    public function remove(Request $request, User $member): RedirectResponse
    {
        $team = $this->ownedTeam($request);
        abort_unless($member->team_id === $team->id && $member->id !== $team->owner_id, 404);
        $member->forceFill(['team_id' => null, 'team_joined_at' => null, 'watch_scope' => 'me'])->save();

        return back()->with('status', $member->name.' is no longer on the team.');
    }

    public function seats(Request $request): RedirectResponse
    {
        $team = $this->ownedTeam($request);
        $sub = $team->subscription();
        if (! $sub) {
            return back()->withErrors(['seats' => 'Seats on this team are set by EMMA. Write to '.config('atlas.contact').'.']);
        }
        $data = $request->validate(['seats' => ['required', 'integer', 'min:'.config('atlas.teams_min_seats'), 'max:'.config('atlas.teams_max_seats')]]);
        if ($data['seats'] < $team->seatsUsed()) {
            return back()->withErrors(['seats' => 'Remove members or withdraw invitations before you go below '.$team->seatsUsed().' seats.']);
        }
        if ($data['seats'] !== Billing::quantity($sub)) {
            $sub->updateQuantity($data['seats']);
        }

        return back()->with('status', 'The team has '.$data['seats'].' seats. Paddle prorates the change on your next invoice.');
    }

    public function rename(Request $request): RedirectResponse
    {
        $team = $this->ownedTeam($request);
        $team->update($request->validate(['name' => ['required', 'string', 'max:120']]));

        return back()->with('status', 'Team name saved.');
    }

    public function leave(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->team && $user->team->owner_id !== $user->id, 403);
        $user->forceFill(['team_id' => null, 'team_joined_at' => null, 'watch_scope' => 'me'])->save();

        return redirect()->route('account')->with('status', 'You have left the team.');
    }

    /** Invitation links: sign in or create an account with the invited address, then join. */
    public function accept(Request $request, string $token): View|RedirectResponse
    {
        $inv = TeamInvitation::where('token_hash', TeamInvitation::hashToken($token))->with('team')->first();
        if (! $inv || ! $inv->isOpen()) {
            return view('team.invite', ['state' => 'expired', 'invitation' => $inv]);
        }
        $user = $request->user();
        if (! $user) {
            $exists = User::whereRaw('lower(email) = ?', [Str::lower($inv->email)])->exists();

            return redirect()->guest(route($exists ? 'login' : 'register', ['email' => $inv->email]));
        }
        if (Str::lower($user->email) !== Str::lower($inv->email)) {
            return view('team.invite', ['state' => 'wrong-user', 'invitation' => $inv]);
        }
        if ($user->team_id && $user->team_id !== $inv->team_id) {
            return view('team.invite', ['state' => 'other-team', 'invitation' => $inv]);
        }
        DB::transaction(function () use ($user, $inv) {
            $user->forceFill(['team_id' => $inv->team_id, 'team_joined_at' => $user->team_joined_at ?? now()]);
            // the link reached this inbox, so the address is confirmed
            if (! $user->hasVerifiedEmail()) {
                $user->email_verified_at = now();
            }
            $user->save();
            $inv->forceFill(['accepted_at' => now()])->save();
        });

        return redirect('/')->with('status', 'Welcome to '.$inv->team->name.'.');
    }
}
