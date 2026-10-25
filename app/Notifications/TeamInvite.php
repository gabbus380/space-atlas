<?php

namespace App\Notifications;

use App\Mail\Letters;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvite extends Notification
{
    public function __construct(public TeamInvitation $invitation, public string $token, public User $from) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Letters::teamInvite(
            $this->from->name,
            $this->invitation->team->name,
            url('/invite/'.$this->token),
        )->message();
    }
}
