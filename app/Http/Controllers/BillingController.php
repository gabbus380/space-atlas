<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Support\Billing;
use App\Support\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Paddle\Transaction;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    private function interval(Request $request): string
    {
        return $request->input('interval', $request->query('interval')) === 'month' ? 'month' : 'year';
    }

    public function personal(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $interval = $this->interval($request);
        if (Billing::subscriptionFor($user, 'personal')) {
            return redirect()->route('account')->with('status', 'Personal is already active on your account.');
        }
        if (Plans::tier($user) === 'teams') {
            return redirect()->route('account')->with('status', 'Your team plan already includes everything in Personal.');
        }
        $price = Billing::priceId('personal', $interval);
        $checkout = Billing::ready() && $price
            ? $user->checkout($price)->customData(['subscription_type' => 'personal', 'interval' => $interval])->returnTo(route('billing.done'))
            : null;

        return view('billing.checkout', ['plan' => 'personal', 'interval' => $interval, 'seats' => 1, 'checkout' => $checkout, 'team' => null]);
    }

    public function teamsForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->ownedTeam?->subscription()) {
            return redirect()->route('team')->with('status', 'Your team plan is active.');
        }

        return view('billing.teams', [
            'interval' => $this->interval($request),
            'seats' => max((int) config('atlas.teams_min_seats'), (int) $request->query('seats', config('atlas.teams_min_seats'))),
            'name' => $user->ownedTeam?->name ?? $user->organization ?? '',
            'memberOf' => $user->team_id && $user->team?->owner_id !== $user->id ? $user->team : null,
        ]);
    }

    public function teamsCheckout(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'seats' => ['required', 'integer', 'min:'.config('atlas.teams_min_seats'), 'max:'.config('atlas.teams_max_seats')],
            'interval' => ['required', 'in:year,month'],
        ]);
        if ($user->team_id && $user->team?->owner_id !== $user->id) {
            return back()->withErrors(['name' => 'You are a member of '.$user->team->name.'. Leave that team in your account before you start your own.']);
        }
        $team = $user->ownedTeam ?: Team::create(['name' => $data['name'], 'owner_id' => $user->id]);
        $team->update(['name' => $data['name']]);
        if ($user->team_id !== $team->id) {
            $user->forceFill(['team_id' => $team->id, 'team_joined_at' => now()])->save();
        }
        if ($team->subscription()) {
            return redirect()->route('team')->with('status', 'Your team plan is active.');
        }
        $price = Billing::priceId('teams', $data['interval']);
        $checkout = Billing::ready() && $price
            ? $user->checkout([$price => (int) $data['seats']])->customData(['subscription_type' => 'teams', 'interval' => $data['interval'], 'team_id' => $team->id])->returnTo(route('billing.done'))
            : null;

        return view('billing.checkout', ['plan' => 'teams', 'interval' => $data['interval'], 'seats' => (int) $data['seats'], 'checkout' => $checkout, 'team' => $team]);
    }

    public function done(): View
    {
        return view('billing.done');
    }

    private function subscriptionOrFail(Request $request): \Laravel\Paddle\Subscription
    {
        $plan = $request->input('plan') === 'teams' ? 'teams' : 'personal';
        $sub = Billing::subscriptionFor($request->user(), $plan);
        abort_unless($sub, 404);

        return $sub;
    }

    public function yearly(Request $request): RedirectResponse
    {
        $sub = $this->subscriptionOrFail($request);
        $plan = $sub->type;
        $year = Billing::priceId($plan, 'year');
        if (! $year || Billing::interval($sub) === 'year') {
            return back();
        }
        $sub->swapAndInvoice([$year => Billing::quantity($sub)]);
        $sub->forceFill(['billing_interval' => 'year'])->save();

        return back()->with('status', 'You are on yearly billing now. Paddle charges the prorated difference today and emails the receipt.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $sub = $this->subscriptionOrFail($request);
        $sub->cancel();

        return back()->with('status', 'Your plan ends on '.$sub->ends_at->format('F j, Y').'. Until then everything stays open.');
    }

    public function resume(Request $request): RedirectResponse
    {
        $sub = $this->subscriptionOrFail($request);
        if ($sub->onGracePeriod()) {
            $sub->stopCancelation();
        }

        return back()->with('status', 'Your plan continues and renews as before.');
    }

    public function paymentMethod(Request $request): Response
    {
        return $this->subscriptionOrFail($request)->redirectToUpdatePaymentMethod();
    }

    public function invoice(Request $request, Transaction $transaction): Response
    {
        abort_unless($transaction->billable_type === $request->user()->getMorphClass() && (int) $transaction->billable_id === $request->user()->id, 404);

        return $transaction->redirectToInvoicePdf();
    }
}
