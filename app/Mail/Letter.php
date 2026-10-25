<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

/**
 * One themed HTML letter. Transactional mail has no marketing opt-out.
 * Briefing and news mail always carry a working unsubscribe link.
 */
class Letter
{
    /**
     * @param  list<string>  $paragraphs
     * @param  list<array{label: string, value: string}>  $facts
     * @param  list<array{title: string, text: string, meta: string}>  $items
     */
    public function __construct(
        public string $subject,
        public string $eyebrow,
        public string $title,
        public array $paragraphs = [],
        public array $facts = [],
        public array $items = [],
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
        public ?string $code = null,
        public ?string $note = null,
        public string $kind = 'transactional',
        public ?string $unsubscribeUrl = null,
        public string $greeting = 'Hello,',
    ) {}

    public function preview(): string
    {
        $text = $this->paragraphs[0] ?? $this->title;

        return mb_strlen($text) > 140 ? mb_substr($text, 0, 137).'…' : $text;
    }

    public function logoUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/apple-touch-icon.png';
    }

    /** @return list<string> */
    public function legalLines(): array
    {
        $lines = [
            'EMMA Aerospace Consulting LLP, a limited liability partnership registered in British Columbia, Canada, number LL03251.',
            '1103-11871 Horseshoe Way, 2nd Floor, Richmond, BC V7A 5H5, Canada.',
            'Contact: '.config('atlas.contact').'.',
        ];

        if ($this->kind === 'news') {
            $lines[] = 'You asked to receive news about Sovereign Space ATLAS. This monthly note is that mail.';
        } elseif ($this->kind === 'briefing') {
            $lines[] = 'This morning briefing is part of a paid Sovereign Space ATLAS plan. It is sent to the address on that account.';
        } else {
            $lines[] = 'This message is about your account or contract with EMMA Aerospace Consulting LLP. It is not a marketing email.';
        }

        $lines[] = 'Paid plans are sold by Paddle, our reseller. Paddle collects the card, the billing address and the tax number. Card data never reach EMMA. Prices shown in the atlas are in US dollars and exclude tax. You can withdraw from the first payment of a plan bought online within '.(int) config('atlas.refund_days').' days.';
        $lines[] = 'The atlas is compiled from public sources for professional briefing. It is not legal advice, investment advice, or a statement by any government.';

        return $lines;
    }

    public function message(): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject)
            ->view(['html' => 'mail.letter', 'text' => 'mail.letter-text'], ['letter' => $this]);

        $url = $this->unsubscribeUrl;
        if ($url) {
            $message->withSymfonyMessage(function (Email $email) use ($url) {
                $email->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$url.'>');
                $email->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
        }

        return $message;
    }

    public function sendTo(string $email, bool $sample = false): void
    {
        $subject = $sample ? '[Sample] '.$this->subject : $this->subject;
        $letter = $this;
        Mail::send(['html' => 'mail.letter', 'text' => 'mail.letter-text'], ['letter' => $letter], function ($message) use ($email, $subject, $letter) {
            $message->to($email)->subject($subject);
            if ($letter->unsubscribeUrl) {
                $message->getSymfonyMessage()->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$letter->unsubscribeUrl.'>');
                $message->getSymfonyMessage()->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            }
        });
    }

    public static function unsubscribe(User $user, string $list): string
    {
        return URL::signedRoute('email.unsubscribe', ['user' => $user->id, 'list' => $list]);
    }

    public static function greeting(?object $notifiable): string
    {
        $name = '';
        if (is_object($notifiable) && method_exists($notifiable, 'firstName')) {
            $name = (string) $notifiable->firstName();
        } elseif (is_object($notifiable) && is_string($notifiable->name ?? null) && $notifiable->name !== '') {
            $name = explode(' ', trim($notifiable->name))[0];
        }

        return $name !== '' ? 'Hello '.$name.',' : 'Hello,';
    }
}
