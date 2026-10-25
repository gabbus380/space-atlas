<?php

namespace App\Notifications;

use App\Mail\Letter;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Delivers one themed letter. */
class SystemMail extends Notification
{
    public function __construct(public Letter $letter) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->letter->message();
    }
}
