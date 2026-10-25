<?php

namespace App\Console\Commands;

use App\Mail\Letters;
use App\Models\User;
use Illuminate\Console\Command;

class SendMailSamples extends Command
{
    protected $signature = 'atlas:mail-samples {email} {--only=}';

    protected $description = 'Send one sample of every system letter to an address';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $recipient = User::query()->whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();
        $letters = Letters::samples($recipient);
        $only = mb_strtolower((string) $this->option('only'));
        if ($only !== '') {
            $letters = array_values(array_filter(
                $letters,
                fn ($letter) => $letter->kind === $only || str_contains(mb_strtolower($letter->eyebrow), $only),
            ));
        }
        if ($letters === []) {
            $this->error('No letter matched '.$only.'.');

            return self::FAILURE;
        }
        $pause = config('mail.default') === 'smtp';
        foreach ($letters as $letter) {
            $letter->sendTo($email, true);
            $this->line($letter->subject);
            if ($pause) {
                sleep(1);
            }
        }
        $this->info($recipient
            ? 'Inbox unsubscribe is attached for this account.'
            : 'No account uses that address, so the inbox Unsubscribe button was left off.');
        $this->info('Samples sent to '.$email.'.');

        return self::SUCCESS;
    }
}
