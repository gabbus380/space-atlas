<?php

namespace App\Notifications;

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
        $w = $this->withdrawal;
        $m = (new MailMessage)->subject($this->toCustomer ? 'We received your withdrawal' : 'Withdrawal request #'.$w->id.' from '.$w->email);
        $lines = [
            'Received: '.$w->created_at->timezone('UTC')->format('F j, Y, H:i').' UTC',
            'Name: '.$w->name,
            'Email: '.$w->email,
            'Address: '.($w->address ?: 'not given'),
            'Order: '.($w->order_reference ?: 'not given'),
            'Ordered on: '.($w->ordered_on?->format('F j, Y') ?: 'not given'),
        ];
        if ($this->toCustomer) {
            $m->greeting('Hello '.$w->name.',')
                ->line('This confirms that we received your withdrawal from your Sovereign Space ATLAS contract.');
        } else {
            $m->greeting('Withdrawal request')->line('Process the refund in Paddle and cancel the subscription.');
        }
        foreach ($lines as $l) {
            $m->line($l);
        }
        if ($w->message) {
            $m->line('Message: '.$w->message);
        }

        return $m->line($this->toCustomer ? 'We process it within 14 days. Paddle, our reseller, returns the payment to the method you used.' : '');
    }
}
