<?php

namespace App\Console\Commands;

use App\Mail\Letter;
use App\Mail\Letters;
use App\Models\EmailDispatch;
use App\Models\User;
use App\Notifications\SystemMail;
use App\Support\AtlasData;
use Illuminate\Console\Command;

class SendMonthlyNewsletter extends Command
{
    protected $signature = 'atlas:mail-newsletter';

    protected $description = 'Email the monthly atlas note to accounts that asked for news';

    public function handle(AtlasData $atlas): int
    {
        $countries = count($atlas->atlas()['countries'] ?? []);
        $organizations = count($atlas->atlas()['orgs'] ?? []);
        $latest = collect($atlas->briefings())->first();
        $headline = is_array($latest) ? (isset($latest['headline']) ? (string) $latest['headline'] : null) : null;
        $month = now('UTC')->format('Y-m');
        $sent = 0;

        User::query()
            ->whereNotNull('email_verified_at')
            ->where('marketing_opt_in', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($countries, $organizations, $headline, $month, &$sent) {
                foreach ($users as $user) {
                    $ok = EmailDispatch::once('newsletter:'.$month.':'.$user->id, function () use ($user, $countries, $organizations, $headline) {
                        $user->notify(new SystemMail(Letters::newsletter(
                            $user,
                            $countries,
                            $organizations,
                            $headline,
                            Letter::unsubscribe($user, 'news'),
                        )));
                    });
                    if ($ok) {
                        $sent++;
                    }
                }
            });
        $this->info('Monthly note for '.$month.' sent to '.$sent.' accounts.');

        return self::SUCCESS;
    }
}
