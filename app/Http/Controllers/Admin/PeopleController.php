<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Plans;
use App\Support\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeopleController extends Controller
{
    public function users(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $users = User::with('team')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('organization', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'tiers' => $users->getCollection()->mapWithKeys(fn (User $user) => [$user->id => Plans::tier($user)]),
        ]);
    }

    public function user(User $user): View
    {
        $user->load(['team.owner', 'ownedTeam']);

        return view('admin.users.show', [
            'user' => $user,
            'tier' => Plans::tier($user),
            'canRoles' => Staff::allows(auth()->user(), 'roles'),
        ]);
    }

    public function grant(Request $request, User $user): RedirectResponse
    {
        $days = (int) $request->validate(['days' => ['required', 'integer', 'min:1', 'max:730']])['days'];
        $user->forceFill(['plan_override' => 'personal', 'override_until' => today()->addDays($days)])->save();

        return back()->with('status', $user->email.' has Personal until '.$user->override_until->format('F j, Y').'.');
    }

    public function revoke(User $user): RedirectResponse
    {
        $user->forceFill(['plan_override' => null, 'override_until' => null])->save();
        $user->ownedTeam?->forceFill(['override_until' => null, 'override_seats' => null])->save();

        return back()->with('status', 'Complimentary access removed for '.$user->email.'.');
    }

    public function verify(User $user): RedirectResponse
    {
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        return back()->with('status', $user->email.' is confirmed.');
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $role = $request->validate(['staff_role' => ['nullable', 'in:'.implode(',', array_keys(Staff::ROLES))]])['staff_role'] ?? null;
        if ($user->is($request->user()) && $role !== Staff::ADMIN) {
            return back()->withErrors(['staff_role' => 'You cannot remove your own admin role.']);
        }
        if ($user->staffRole() === Staff::ADMIN && $role !== Staff::ADMIN && $this->adminCount() <= 1) {
            return back()->withErrors(['staff_role' => 'Keep at least one admin.']);
        }
        $user->assignStaffRole($role ?: null);

        return back()->with('status', $user->email.' is now '.($role ? Staff::ROLES[$role] : 'a regular account').'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own account from here.']);
        }
        if ($user->staffRole() && ! Staff::allows($request->user(), 'roles')) {
            return back()->withErrors(['user' => 'Only an admin or a manager can delete a staff account.']);
        }
        if ($user->staffRole() === Staff::ADMIN && $this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'Keep at least one admin.']);
        }
        foreach (['personal', 'teams'] as $plan) {
            $subscription = $user->subscription($plan);
            if ($subscription && $subscription->valid() && ! $subscription->canceled()) {
                return back()->withErrors(['user' => 'Cancel the '.ucfirst($plan).' plan before deleting this account.']);
            }
        }
        if ($user->ownedTeam && $user->ownedTeam->members()->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(['user' => 'This account still owns a team with other members.']);
        }
        $email = $user->email;
        $user->ownedTeam?->delete();
        $user->delete();

        return redirect('/admin/users')->with('status', $email.' is deleted.');
    }

    public function teams(): View
    {
        return view('admin.teams.index', [
            'teams' => Team::with('owner')->withCount('members')->latest()->paginate(30),
        ]);
    }

    public function team(Team $team): View
    {
        $team->load(['owner', 'members', 'invitations']);

        return view('admin.teams.show', ['team' => $team]);
    }

    public function teamGrant(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'seats' => ['required', 'integer', 'min:1', 'max:'.config('atlas.teams_max_seats')],
            'days' => ['required', 'integer', 'min:1', 'max:800'],
        ]);
        $team->forceFill([
            'override_until' => today()->addDays($data['days']),
            'override_seats' => $data['seats'],
        ])->save();

        return back()->with('status', $team->name.' has '.$data['seats'].' seats until '.$team->override_until->format('F j, Y').'.');
    }

    public function teamRevoke(Team $team): RedirectResponse
    {
        $team->forceFill(['override_until' => null, 'override_seats' => null])->save();

        return back()->with('status', 'Complimentary team access removed for '.$team->name.'.');
    }

    public function withdrawals(): View
    {
        return view('admin.withdrawals.index', [
            'withdrawals' => Withdrawal::query()->latest()->paginate(30),
        ]);
    }

    public function withdrawal(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,done,declined'],
            'staff_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $withdrawal->forceFill([
            'status' => $data['status'],
            'staff_note' => $data['staff_note'] ?? null,
            'handled_at' => $data['status'] === 'open' ? null : now(),
            'handled_by' => $data['status'] === 'open' ? null : $request->user()->id,
        ])->save();

        return back()->with('status', 'Withdrawal from '.$withdrawal->email.' marked '.$data['status'].'.');
    }

    private function adminCount(): int
    {
        return User::query()
            ->where('staff_role', Staff::ADMIN)
            ->orWhere(fn ($query) => $query->where('is_admin', true)->whereNull('staff_role'))
            ->count();
    }
}
