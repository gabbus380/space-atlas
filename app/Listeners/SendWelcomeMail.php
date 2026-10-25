<?php

namespace App\Listeners;

use App\Mail\Letter;
use App\Mail\Letters;
use App\Models\EmailDispatch;
use App\Models\User;
use App\Notifications\SystemMail;
use Illuminate\Auth\Events\Verified;

class SendWelcomeMail
{
    public function handle(Verified $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        EmailDispatch::once('welcome:'.$user->id, function () use ($user) {
            $user->notify(new SystemMail(Letters::welcome($user)));
        });
    }
}
