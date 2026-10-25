<?php

namespace App\Listeners;

use Illuminate\Support\Carbon;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Subscription;

/** Keeps the next billing date and the interval on our copy of each subscription, for the account page. */
class SyncSubscriptionMeta
{
    public function handle(WebhookHandled $event): void
    {
        $type = $event->payload['event_type'] ?? '';
        if (! str_starts_with($type, 'subscription.')) {
            return;
        }
        $d = $event->payload['data'] ?? [];
        $sub = isset($d['id']) ? Subscription::where('paddle_id', $d['id'])->first() : null;
        if (! $sub) {
            return;
        }
        $sub->forceFill([
            'next_billed_at' => ! empty($d['next_billed_at']) ? Carbon::parse($d['next_billed_at'], 'UTC') : null,
            'billing_interval' => $d['billing_cycle']['interval'] ?? $sub->billing_interval,
        ])->save();
    }
}
