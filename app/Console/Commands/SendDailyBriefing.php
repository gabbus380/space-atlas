<?php

namespace App\Console\Commands;

use App\Mail\Letter;
use App\Mail\Letters;
use App\Models\EmailDispatch;
use App\Models\User;
use App\Notifications\SystemMail;
use App\Support\AtlasData;
use App\Support\Plans;
use Illuminate\Console\Command;

class SendDailyBriefing extends Command
{
    protected $signature = 'atlas:mail-briefing';

    protected $description = 'Email the latest filed morning briefing to paid accounts that still want it';

    public function handle(AtlasData $atlas): int
    {
        $edition = collect($atlas->briefings())->first();
        if (! is_array($edition) || empty($edition['date'])) {
            $this->info('No briefing is filed.');

            return self::SUCCESS;
        }

        $date = (string) $edition['date'];
        $sent = 0;
        User::query()
            ->whereNotNull('email_verified_at')
            ->where('briefing_opt_out', false)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($edition, $date, &$sent) {
                foreach ($users as $user) {
                    if (! Plans::isPaid(Plans::tier($user))) {
                        continue;
                    }
                    $ok = EmailDispatch::once('briefing:'.$date.':'.$user->id, function () use ($user, $edition) {
                        $user->notify(new SystemMail(Letters::briefing($edition, Letter::unsubscribe($user, 'briefing'))));
                    });
                    if ($ok) {
                        $sent++;
                    }
                }
            });
        $this->info('Briefing '.$date.' sent to '.$sent.' accounts.');

        return self::SUCCESS;
    }
}
