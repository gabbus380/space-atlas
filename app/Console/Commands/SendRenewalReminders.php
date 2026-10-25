<?php

namespace App\Console\Commands;

use App\Mail\Letters;
use App\Models\EmailDispatch;
use App\Models\User;
use App\Notifications\SystemMail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Paddle\Subscription;

class SendRenewalReminders extends Command
{
    protected $signature = 'atlas:mail-renewals';

    protected $description = 'Email accounts whose plan renews or ends within seven days';

    public function handle(): int
    {
        $until = now()->addDays(7);
        $sent = 0;
        Subscription::query()->with('billable')->orderBy('id')->each(function (Subscription $subscription) use ($until, &$sent) {
            $user = $subscription->billable;
            if (! $user instanceof User) {
                return;
            }
            $plan = $subscription->type ?: 'atlas';
            $ends = $subscription->ends_at ? Carbon::parse($subscription->ends_at) : null;
            $renews = $subscription->next_billed_at ? Carbon::parse($subscription->next_billed_at) : null;

            if ($ends && $ends->between(now(), $until)) {
                $date = $ends->timezone('UTC')->format('F j, Y');
                $ok = EmailDispatch::once('ending:'.$subscription->id.':'.$ends->toDateString(), function () use ($user, $plan, $date) {
                    $user->notify(new SystemMail(Letters::accessEnding($user, $plan, $date)));
                });
            } elseif ($subscription->status === 'active' && $renews && $renews->between(now(), $until)) {
                $date = $renews->timezone('UTC')->format('F j, Y');
                $ok = EmailDispatch::once('renewal:'.$subscription->id.':'.$renews->toDateString(), function () use ($user, $plan, $date) {
                    $user->notify(new SystemMail(Letters::renewalReminder($user, $plan, $date)));
                });
            } else {
                return;
            }
            if ($ok) {
                $sent++;
            }
        });
        $this->info('Renewal letters sent: '.$sent.'.');

        return self::SUCCESS;
    }
}
