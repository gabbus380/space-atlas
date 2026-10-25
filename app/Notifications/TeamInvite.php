<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject($this->from->name.' invited you to '.$this->invitation->team->name.' on Sovereign Space ATLAS')
            ->greeting('Hello,')
            ->line($this->from->name.' added you to the '.$this->invitation->team->name.' team on Sovereign Space ATLAS. The seat is paid by the team.')
            ->action('Join the team', url('/invite/'.$this->token))
            ->line('The link works for 14 days. If you did not expect this, you can ignore this email.');
    }
}
