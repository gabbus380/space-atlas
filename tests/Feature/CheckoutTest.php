<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'cashier.api_key' => 'test_key', 'cashier.client_side_token' => 'test_token', 'cashier.sandbox' => true,
            'atlas.paddle_prices.personal.year' => 'pri_py', 'atlas.paddle_prices.personal.month' => 'pri_pm',
            'atlas.paddle_prices.teams.year' => 'pri_ty', 'atlas.paddle_prices.teams.month' => 'pri_tm',
        ]);
        Http::fake([
            'sandbox-api.paddle.com/customers*' => Http::sequence()
                ->push(['data' => []])
                ->push(['data' => ['id' => 'ctm_1', 'name' => 'Test', 'email' => 'buyer@example.org']]),
        ]);
    }

    public function test_personal_checkout_renders_paddle_button(): void
    {
        $u = User::factory()->create(['email' => 'buyer@example.org']);
        $r = $this->actingAs($u)->get('/subscribe/personal?interval=year')->assertOk();
        $r->assertSee('cdn.paddle.com/paddle/v2/paddle.js', false)->assertSee('Continue to secure checkout')->assertSee('pri_py')->assertSee('{"subscription_type":"personal","interval":"year"}');
        $r->assertSee('Paddle.com is the merchant of record');
    }

    public function test_teams_checkout_carries_seat_quantity(): void
    {
        $u = User::factory()->create(['email' => 'buyer@example.org']);
        $r = $this->actingAs($u)->post('/subscribe/teams', ['name' => 'Orbit Ltd', 'seats' => 5, 'interval' => 'month'])->assertOk();
        $r->assertSee('"quantity":5', false)->assertSee('pri_tm')->assertSee('saves $600');
    }

    public function test_webhook_meta_listener_stores_renewal_date(): void
    {
        $u = User::factory()->create();
        $s = Subscription::forceCreate(['billable_type' => $u->getMorphClass(), 'billable_id' => $u->id, 'type' => 'personal', 'paddle_id' => 'sub_9', 'status' => 'active']);
        event(new WebhookHandled(['event_type' => 'subscription.updated', 'data' => ['id' => 'sub_9', 'next_billed_at' => '2027-10-23T10:00:00Z', 'billing_cycle' => ['interval' => 'year', 'frequency' => 1]]]));
        $s->refresh();
        $this->assertSame('year', $s->billing_interval);
        $this->assertSame('2027-10-23', \Illuminate\Support\Carbon::parse($s->next_billed_at)->toDateString());
    }

    public function test_account_and_team_pages_with_live_subscriptions(): void
    {
        $u = User::factory()->create();
        $s = Subscription::forceCreate(['billable_type' => $u->getMorphClass(), 'billable_id' => $u->id, 'type' => 'personal', 'paddle_id' => 'sub_p', 'status' => 'active', 'next_billed_at' => now()->addMonth()]);
        $s->items()->forceCreate(['product_id' => 'pro_1', 'price_id' => 'pri_pm', 'status' => 'active', 'quantity' => 1]);
        $this->actingAs($u)->get('/account')->assertOk()->assertSee('billed monthly at $29')->assertSee('Switch to yearly')->assertSee('Renews')->assertSee('Founding rate');

        $o = User::factory()->create();
        $team = \App\Models\Team::create(['name' => 'Orbit Ltd', 'owner_id' => $o->id]);
        $o->forceFill(['team_id' => $team->id, 'team_joined_at' => now()])->save();
        $t = Subscription::forceCreate(['billable_type' => $o->getMorphClass(), 'billable_id' => $o->id, 'type' => 'teams', 'paddle_id' => 'sub_t', 'status' => 'active']);
        $t->items()->forceCreate(['product_id' => 'pro_2', 'price_id' => 'pri_tm', 'status' => 'active', 'quantity' => 4]);
        $this->actingAs($o)->get('/team')->assertOk()->assertSee('4 seats, 1 in use')->assertSee('billed monthly')->assertSee('save $480 a year');
        $this->post('/team/seats', ['seats' => 2])->assertSessionHasErrors('seats');
    }
}
