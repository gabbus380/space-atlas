<?php

namespace App\Notifications;

use App\Mail\Letters;
use App\Models\Withdrawal;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalReceived extends Notification
{
    public function __construct(public Withdrawal $withdrawal, public bool $toCustomer) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return Letters::withdrawal($this->withdrawal, $this->toCustomer)->message();
    }
}
