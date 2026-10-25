<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;

/** Copy for every system letter. The HTML shell is resources/views/mail/letter.blade.php. */
class Letters
{
    public static function verify(object $notifiable, string $url): Letter
    {
        return new Letter(
            subject: 'Confirm your email for Sovereign Space ATLAS',
            eyebrow: 'Registration',
            title: 'Confirm this address',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'Your Sovereign Space ATLAS account is created. Confirm this address to open full country files and to subscribe.',
                'The link expires after a while. If it does, sign in and ask for a new one.',
            ],
            actionLabel: 'Confirm email address',
            actionUrl: $url,
            note: 'If you did not create an account, you can ignore this email. No plan is started until you subscribe.',
        );
    }

    public static function welcome(object $notifiable): Letter
    {
        return new Letter(
            subject: 'Your Sovereign Space ATLAS account is open',
            eyebrow: 'Welcome',
            title: 'The atlas is open',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'This address is confirmed. The open atlas is available now: the map, the public country layer, and the plans.',
                'A Personal or Teams plan opens every country file, the morning briefing, and the archive. Teams also shares a watchlist and notes.',
            ],
            actionLabel: 'Open the atlas',
            actionUrl: url('/'),
            note: 'You can review Terms, Privacy, and Refunds from the links below before you subscribe.',
        );
    }

    public static function resetPassword(object $notifiable, string $url): Letter
    {
        return new Letter(
            subject: 'Reset your Sovereign Space ATLAS password',
            eyebrow: 'Account security',
            title: 'Choose a new password',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'Someone asked to reset the password for this account. The link works for 60 minutes.',
            ],
            actionLabel: 'Choose a new password',
            actionUrl: $url,
            note: 'If it was not you, no change is made. You can ignore this email.',
        );
    }

    public static function teamInvite(string $fromName, string $teamName, string $url): Letter
    {
        return new Letter(
            subject: $fromName.' invited you to '.$teamName.' on Sovereign Space ATLAS',
            eyebrow: 'Team',
            title: 'Join '.$teamName,
            paragraphs: [
                $fromName.' added you to the '.$teamName.' team on Sovereign Space ATLAS. The seat is paid by the team.',
                'The link works for 14 days. Accepting it uses one seat and opens the team plan on this address.',
            ],
            actionLabel: 'Join the team',
            actionUrl: $url,
            note: 'If you did not expect this, you can ignore this email. No account is created until you accept.',
        );
    }

    public static function withdrawal(Withdrawal $withdrawal, bool $toCustomer): Letter
    {
        $received = $withdrawal->created_at?->timezone('UTC')->format('F j, Y, H:i').' UTC';
        $facts = [
            ['label' => 'Received', 'value' => $received ?: 'just now'],
            ['label' => 'Name', 'value' => $withdrawal->name],
            ['label' => 'Email', 'value' => $withdrawal->email],
            ['label' => 'Address', 'value' => $withdrawal->address ?: 'not given'],
            ['label' => 'Order', 'value' => $withdrawal->order_reference ?: 'not given'],
            ['label' => 'Ordered on', 'value' => $withdrawal->ordered_on?->format('F j, Y') ?: 'not given'],
        ];
        if ($toCustomer) {
            return new Letter(
                subject: 'We received your withdrawal',
                eyebrow: 'Withdrawal',
                title: 'Your withdrawal is in',
                greeting: 'Hello '.$withdrawal->name.',',
                paragraphs: [
                    'This confirms that EMMA Aerospace Consulting LLP received your withdrawal from your Sovereign Space ATLAS contract.',
                    'We process it within '.(int) config('atlas.refund_days').' days. Paddle, our reseller, returns the payment to the method you used.',
                ],
                facts: $facts,
                note: $withdrawal->message ? 'Your message: '.$withdrawal->message : null,
            );
        }

        return new Letter(
            subject: 'Withdrawal request #'.$withdrawal->id.' from '.$withdrawal->email,
            eyebrow: 'Operator',
            title: 'Withdrawal to process',
            greeting: 'Hello,',
            paragraphs: [
                'A reader withdrew from a contract. Process the refund in Paddle and cancel the subscription.',
            ],
            facts: $facts,
            actionLabel: 'Open withdrawals',
            actionUrl: url('/admin/withdrawals'),
            note: $withdrawal->message ? 'Message: '.$withdrawal->message : null,
        );
    }

    public static function subscriptionStarted(object $notifiable, string $plan, ?string $renewsOn): Letter
    {
        return new Letter(
            subject: 'Your '.ucfirst($plan).' plan is active',
            eyebrow: 'Subscription',
            title: ucfirst($plan).' is active',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'The '.$plan.' plan on Sovereign Space ATLAS is active. Country files, the morning briefing, and the archive are open on this account.',
                'Paddle will email its own receipt. This note is the record from EMMA Aerospace Consulting LLP.',
            ],
            facts: array_values(array_filter([
                ['label' => 'Plan', 'value' => ucfirst($plan)],
                $renewsOn ? ['label' => 'Next bill', 'value' => $renewsOn] : null,
            ])),
            actionLabel: 'Open your account',
            actionUrl: url('/account'),
        );
    }

    public static function paymentReceived(object $notifiable, ?string $amount, ?string $invoice, string $plan): Letter
    {
        return new Letter(
            subject: 'Payment received for Sovereign Space ATLAS',
            eyebrow: 'Payment',
            title: 'Payment received',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'Paddle collected a payment for your '.$plan.' plan. The invoice, when one is issued, is also on your account page.',
            ],
            facts: array_values(array_filter([
                ['label' => 'Plan', 'value' => ucfirst($plan)],
                $amount ? ['label' => 'Total', 'value' => $amount] : null,
                $invoice ? ['label' => 'Invoice', 'value' => $invoice] : null,
            ])),
            actionLabel: 'View the account',
            actionUrl: url('/account'),
            note: 'The first payment of a plan bought online can be withdrawn within '.(int) config('atlas.refund_days').' days.',
        );
    }

    public static function paymentFailed(object $notifiable, string $plan): Letter
    {
        return new Letter(
            subject: 'Payment failed for your Sovereign Space ATLAS plan',
            eyebrow: 'Payment',
            title: 'The payment did not clear',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'Paddle could not collect the latest payment for your '.$plan.' plan. Update the card so the plan stays open.',
            ],
            actionLabel: 'Open your account',
            actionUrl: url('/account'),
            note: 'If you already updated the card, you can ignore a later copy of this note.',
        );
    }

    public static function subscriptionCanceled(object $notifiable, string $plan, ?string $endsOn): Letter
    {
        return new Letter(
            subject: 'Your '.ucfirst($plan).' plan is cancelled',
            eyebrow: 'Subscription',
            title: ucfirst($plan).' will end',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                $endsOn
                    ? 'The '.$plan.' plan stays open until '.$endsOn.'. After that the account returns to the free atlas.'
                    : 'The '.$plan.' plan is cancelled. When the paid period ends, the account returns to the free atlas.',
            ],
            actionLabel: 'Open your account',
            actionUrl: url('/account'),
        );
    }

    public static function subscriptionPaused(object $notifiable, string $plan): Letter
    {
        return new Letter(
            subject: 'Your '.ucfirst($plan).' plan is paused',
            eyebrow: 'Subscription',
            title: ucfirst($plan).' is paused',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'The '.$plan.' plan is paused. Paid country files and the morning briefing stay closed until it resumes.',
            ],
            actionLabel: 'Open your account',
            actionUrl: url('/account'),
        );
    }

    public static function renewalReminder(object $notifiable, string $plan, string $renewsOn): Letter
    {
        return new Letter(
            subject: 'Your '.ucfirst($plan).' plan renews on '.$renewsOn,
            eyebrow: 'Renewal',
            title: 'Renewal in the coming days',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'Your '.$plan.' plan renews on '.$renewsOn.'. Paddle will charge the payment method on file.',
                'Cancel before that date if you do not want another term. The current term stays open until it ends.',
            ],
            actionLabel: 'Review the plan',
            actionUrl: url('/account'),
        );
    }

    public static function accessEnding(object $notifiable, string $plan, string $endsOn): Letter
    {
        return new Letter(
            subject: 'Your '.ucfirst($plan).' plan ends on '.$endsOn,
            eyebrow: 'Subscription',
            title: 'Paid access is ending',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'Your '.$plan.' plan ends on '.$endsOn.'. After that date the account keeps the free atlas: the map and the public layer.',
                'Country files, the morning briefing, and the archive close with the plan.',
            ],
            actionLabel: 'See plans',
            actionUrl: url('/pricing'),
        );
    }

    /** @param  array<string, mixed>  $edition */
    public static function briefing(array $edition, ?string $unsubscribeUrl): Letter
    {
        $date = (string) ($edition['date'] ?? '');
        $when = $date !== '' ? Carbon::parse($date)->format('F j, Y') : 'the latest edition';
        $items = [];
        foreach (array_slice($edition['global'] ?? [], 0, 5) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $bits = [];
            foreach (['iso3', 'priority'] as $key) {
                $value = $item[$key] ?? null;
                if (is_scalar($value) && (string) $value !== '') {
                    $bits[] = (string) $value;
                }
            }
            $meta = implode(' · ', $bits);
            $items[] = [
                'title' => is_scalar($item['title'] ?? null) ? (string) $item['title'] : 'Update',
                'text' => is_scalar($item['summary'] ?? null) ? (string) $item['summary'] : '',
                'meta' => $meta,
            ];
        }
        $paragraphs = array_values(array_filter([
            is_string($edition['summary'] ?? null) ? $edition['summary'] : null,
            self::bottomLine($edition['bottom_line'] ?? null),
        ]));

        return new Letter(
            subject: 'Morning briefing · '.$when,
            eyebrow: 'Daily briefing · '.(is_scalar($edition['edition'] ?? null) ? (string) $edition['edition'] : '06:00 UTC'),
            title: is_scalar($edition['headline'] ?? null) ? (string) $edition['headline'] : 'Morning briefing',
            paragraphs: $paragraphs !== [] ? $paragraphs : ['The morning edition is filed. Open the atlas for the map and the country layer.'],
            items: $items,
            actionLabel: 'Open this edition',
            actionUrl: url('/'),
            note: 'This edition is dated '.$when.'. The archive keeps '.(int) config('atlas.archive_days').' days.',
            kind: 'briefing',
            unsubscribeUrl: $unsubscribeUrl,
        );
    }

    public static function newsletter(object $notifiable, int $countries, int $organizations, ?string $headline, ?string $unsubscribeUrl): Letter
    {
        $month = now('UTC')->format('F Y');

        return new Letter(
            subject: 'Sovereign Space ATLAS · '.$month,
            eyebrow: 'Monthly note · '.$month,
            title: 'What the atlas holds',
            greeting: Letter::greeting($notifiable),
            paragraphs: array_values(array_filter([
                'This is the monthly note you asked for. It describes the atlas. It does not add reporting that is not in the product.',
                $headline ? 'Latest filed briefing: '.$headline : null,
            ])),
            facts: [
                ['label' => 'Countries', 'value' => (string) $countries],
                ['label' => 'Organizations', 'value' => (string) $organizations],
                ['label' => 'Briefing archive', 'value' => (int) config('atlas.archive_days').' days on a paid plan'],
            ],
            actionLabel: 'Open the atlas',
            actionUrl: url('/'),
            note: 'A paid plan opens every country file and the morning briefing. The open atlas stays free.',
            kind: 'news',
            unsubscribeUrl: $unsubscribeUrl,
        );
    }

    public static function apiKey(string $organization, string $plain, int $quota, bool $rotated): Letter
    {
        return new Letter(
            subject: ($rotated ? 'Your API key was replaced' : 'Your Sovereign Space ATLAS API key'),
            eyebrow: 'API',
            title: $rotated ? 'The previous key no longer works' : 'Your API key',
            paragraphs: [
                $rotated
                    ? 'A new key was issued for '.$organization.'. The previous key stopped working when this one was created.'
                    : 'An API key was issued for '.$organization.'. Store it now. Sovereign Space ATLAS cannot show it again.',
                'Send it as a bearer token or in the X-API-Key header. The daily quota is '.$quota.' requests.',
            ],
            code: $plain,
            actionLabel: 'Read the API docs',
            actionUrl: url('/account/api'),
            note: 'If you did not expect this key, write to '.config('atlas.contact').' and we will switch it off.',
        );
    }

    public static function complimentary(object $notifiable, string $until): Letter
    {
        return new Letter(
            subject: 'Complimentary Personal access until '.$until,
            eyebrow: 'Access',
            title: 'Personal is open',
            greeting: Letter::greeting($notifiable),
            paragraphs: [
                'EMMA Aerospace Consulting LLP granted complimentary Personal access on this account until '.$until.'.',
                'That opens every country file, the morning briefing, and the archive. It is not a Paddle subscription and it is not charged.',
            ],
            actionLabel: 'Open the atlas',
            actionUrl: url('/'),
        );
    }

    /** @return list<Letter> */
    public static function samples(): array
    {
        $reader = new User(['name' => 'Gabor Magyari', 'email' => 'gabor.magyari@emmaconsulting.space']);
        $account = url('/account');
        $withdrawal = new Withdrawal([
            'name' => 'Gabor Magyari',
            'email' => 'gabor.magyari@emmaconsulting.space',
            'address' => 'Richmond, BC, Canada',
            'order_reference' => 'SAMPLE-ORDER',
            'message' => 'This is a sample withdrawal, not a real request.',
        ]);
        $withdrawal->id = 0;
        $withdrawal->created_at = now('UTC');
        $withdrawal->ordered_on = now('UTC')->toDateString();

        $edition = [
            'date' => now('UTC')->toDateString(),
            'edition' => '06:00 UTC',
            'headline' => 'Sample morning briefing',
            'summary' => 'This sample uses the letter design. A real edition uses the briefing filed in the atlas.',
            'bottom_line' => 'Open the atlas for the filed edition.',
            'global' => [[
                'iso3' => 'CAN',
                'priority' => 'watch',
                'title' => 'Sample item',
                'summary' => 'Real briefings list the global items filed that morning.',
            ]],
        ];

        return [
            self::verify($reader, url('/email/verify')),
            self::welcome($reader),
            self::resetPassword($reader, url('/login')),
            self::teamInvite('Ada Lovelace', 'EMMA Aerospace Consulting', url('/login')),
            self::withdrawal($withdrawal, true),
            self::withdrawal($withdrawal, false),
            self::subscriptionStarted($reader, 'personal', now('UTC')->addYear()->format('F j, Y')),
            self::paymentReceived($reader, 'USD 228.00', 'SAMPLE-INVOICE', 'personal'),
            self::paymentFailed($reader, 'personal'),
            self::subscriptionCanceled($reader, 'personal', now('UTC')->addMonth()->format('F j, Y')),
            self::subscriptionPaused($reader, 'teams'),
            self::renewalReminder($reader, 'personal', now('UTC')->addDays(7)->format('F j, Y')),
            self::accessEnding($reader, 'personal', now('UTC')->addDays(7)->format('F j, Y')),
            self::briefing($edition, $account),
            self::newsletter($reader, 81, 419, 'Sample headline from the latest filed briefing', $account),
            self::apiKey('EMMA Aerospace Consulting', 'ssa_live_sample_not_a_real_key', 10000, false),
            self::complimentary($reader, now('UTC')->addYear()->format('F j, Y')),
        ];
    }

    private static function bottomLine(mixed $line): ?string
    {
        if (is_string($line) && $line !== '') {
            return $line;
        }
        if (! is_array($line)) {
            return null;
        }
        $text = $line['text'] ?? $line['summary'] ?? null;

        return is_string($text) && $text !== '' ? $text : null;
    }
}
