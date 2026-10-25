<?php

namespace App\Console\Commands;

use App\Mail\Letters;
use Illuminate\Console\Command;

class SendMailSamples extends Command
{
    protected $signature = 'atlas:mail-samples {email}';

    protected $description = 'Send one sample of every system letter to an address';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $pause = config('mail.default') === 'smtp';
        foreach (Letters::samples() as $letter) {
            $letter->sendTo($email, true);
            $this->line($letter->subject);
            if ($pause) {
                sleep(1);
            }
        }
        $this->info('Samples sent to '.$email.'.');

        return self::SUCCESS;
    }
}
