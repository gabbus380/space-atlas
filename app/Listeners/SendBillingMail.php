<?php

namespace App\Listeners;

use App\Mail\Letters;
use App\Models\EmailDispatch;
use App\Models\User;
use App\Notifications\SystemMail;
use Illuminate\Support\Carbon;
use Laravel\Paddle\Customer;
use Laravel\Paddle\Events\WebhookHandled;

/** Turns Paddle billing events into account letters. Syncing the subscription stays in SyncSubscriptionMeta. */
class SendBillingMail
{
    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $type = (string) ($payload['event_type'] ?? '');
        $data = $payload['data'] ?? [];
        $user = $this->user($data);
        if (! $user || ! is_array($data)) {
            return;
        }

        $plan = $this->plan($data);
        if ($type === 'subscription.created' && ! in_array($data['status'] ?? '', ['active', 'trialing'], true)) {
            return;
        }
        $letter = match ($type) {
            'subscription.activated', 'subscription.created' => Letters::subscriptionStarted($user, $plan, $this->date($data['next_billed_at'] ?? null)),
            'transaction.completed' => Letters::paymentReceived($user, $this->money($data), is_string($data['invoice_number'] ?? null) ? $data['invoice_number'] : null, $plan),
            'transaction.payment_failed' => Letters::paymentFailed($user, $plan),
            'subscription.canceled' => Letters::subscriptionCanceled($user, $plan, $this->date($data['scheduled_change']['effective_at'] ?? $data['canceled_at'] ?? null)),
            'subscription.paused' => Letters::subscriptionPaused($user, $plan),
            default => null,
        };
        if (! $letter) {
            return;
        }

        $key = $this->dedupe($type, $payload, $data);
        EmailDispatch::once($key, fn () => $user->notify(new SystemMail($letter)));
    }

    private function user(array $data): ?User
    {
        $customerId = $data['customer_id'] ?? null;
        if (! is_string($customerId) || $customerId === '') {
            return null;
        }
        $billable = Customer::query()->where('paddle_id', $customerId)->first()?->billable;

        return $billable instanceof User ? $billable : null;
    }

    private function plan(array $data): string
    {
        $custom = $data['custom_data']['subscription_type'] ?? $data['custom_data']['plan'] ?? null;
        if (is_string($custom) && $custom !== '') {
            return $custom;
        }
        $name = $data['items'][0]['price']['name'] ?? $data['items'][0]['product']['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : 'atlas';
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Carbon::parse($value, 'UTC')->format('F j, Y');
    }

    private function money(array $data): ?string
    {
        $cents = $data['details']['totals']['grand_total'] ?? null;
        if (! is_numeric($cents)) {
            return null;
        }
        $currency = is_string($data['currency_code'] ?? null) ? $data['currency_code'] : 'USD';

        return $currency.' '.number_format(((int) $cents) / 100, 2);
    }

    private function dedupe(string $type, array $payload, array $data): string
    {
        $id = (string) ($data['id'] ?? '');
        if (in_array($type, ['subscription.activated', 'subscription.created'], true) && $id !== '') {
            return 'subscription-started:'.$id;
        }
        if ($type === 'transaction.completed' && $id !== '') {
            return 'payment:'.$id;
        }
        $eventId = $payload['event_id'] ?? $payload['notification_id'] ?? null;
        if (is_string($eventId) && $eventId !== '') {
            return 'billing:'.$eventId;
        }

        return 'billing:'.$type.':'.$id.':'.sha1(json_encode($data));
    }
}
