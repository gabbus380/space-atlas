<?php

namespace Tests\Feature;

use App\Mail\Letters;
use App\Models\User;
use App\Notifications\SystemMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Paddle\Customer;
use Laravel\Paddle\Events\WebhookHandled;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

class SystemMailTest extends TestCase
{
    use RefreshDatabase;
    public function test_every_letter_carries_the_theme_and_the_legal_notice(): void
    {
        foreach (Letters::samples() as $letter) {
            $html = view('mail.letter', ['letter' => $letter])->render();
            $text = view('mail.letter-text', ['letter' => $letter])->render();

            $this->assertStringContainsString('Sovereign Space', $html, $letter->subject);
            $this->assertStringContainsString('#e5bf6d', $html, $letter->subject);
            $this->assertStringContainsString('apple-touch-icon.png', $html, $letter->subject);
            $this->assertStringContainsString('EMMA Aerospace Consulting LLP', $html, $letter->subject);
            $this->assertStringContainsString('LL03251', $html, $letter->subject);
            $this->assertStringContainsString('1103-11871 Horseshoe Way', $html, $letter->subject);
            $this->assertStringContainsString('not legal advice', $html, $letter->subject);
            $this->assertStringContainsString($letter->title, $text, $letter->subject);
            if (in_array($letter->kind, ['briefing', 'news'], true)) {
                $this->assertNotNull($letter->unsubscribeUrl, $letter->subject);
                $this->assertStringContainsString('Unsubscribe', $html, $letter->subject);
            }
        }
        $this->assertCount(17, Letters::samples());
    }

    public function test_verification_and_reset_use_the_letter(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Ada Lovelace']);

        $verify = (new VerifyEmail)->toMail($user)->render();
        $this->assertStringContainsString('Confirm this address', $verify);
        $this->assertStringContainsString('LL03251', $verify);

        $reset = (new ResetPassword('token'))->toMail($user)->render();
        $this->assertStringContainsString('Choose a new password', $reset);
        $this->assertStringContainsString('60 minutes', $reset);
    }

    public function test_confirming_an_email_sends_the_welcome_letter_once(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect();
        $this->actingAs($user)->get($url)->assertRedirect();

        Notification::assertSentToTimes($user, SystemMail::class, 1);
        Notification::assertSentTo($user, SystemMail::class, fn (SystemMail $mail) => $mail->letter->eyebrow === 'Welcome');
    }

    public function test_a_collected_payment_sends_one_payment_letter(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        Customer::query()->create([
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'paddle_id' => 'ctm_test',
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $payload = [
            'event_id' => 'evt_payment',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_test',
                'customer_id' => 'ctm_test',
                'invoice_number' => 'INV-1',
                'currency_code' => 'USD',
                'custom_data' => ['subscription_type' => 'personal'],
                'details' => ['totals' => ['grand_total' => '22800']],
            ],
        ];
        event(new WebhookHandled($payload));
        event(new WebhookHandled($payload));

        Notification::assertSentToTimes($user, SystemMail::class, 1);
        Notification::assertSentTo($user, SystemMail::class, function (SystemMail $mail) {
            return $mail->letter->eyebrow === 'Payment'
                && collect($mail->letter->facts)->contains(fn ($fact) => $fact['value'] === 'USD 228.00');
        });
    }

    public function test_unsubscribe_stops_the_list_that_was_named(): void
    {
        $user = User::factory()->create(['marketing_opt_in' => true, 'briefing_opt_out' => false]);

        $news = URL::signedRoute('email.unsubscribe', ['user' => $user->id, 'list' => 'news']);
        $this->get($news)->assertOk()->assertSee('monthly note is off');
        $this->assertFalse($user->fresh()->marketing_opt_in);

        $briefing = URL::signedRoute('email.unsubscribe', ['user' => $user->id, 'list' => 'briefing']);
        $this->post($briefing)->assertOk()->assertSee('unsubscribed');
        $this->assertTrue($user->fresh()->briefing_opt_out);
    }

    public function test_briefing_goes_once_to_a_paid_account_that_still_wants_it(): void
    {
        Notification::fake();
        $paid = User::factory()->create([
            'plan_override' => 'personal',
            'override_until' => now()->addMonth()->toDateString(),
            'briefing_opt_out' => false,
        ]);
        User::factory()->create(['briefing_opt_out' => false]);
        User::factory()->create([
            'plan_override' => 'personal',
            'override_until' => now()->addMonth()->toDateString(),
            'briefing_opt_out' => true,
        ]);

        $this->artisan('atlas:mail-briefing')->assertSuccessful();
        $this->artisan('atlas:mail-briefing')->assertSuccessful();

        Notification::assertSentToTimes($paid, SystemMail::class, 1);
        Notification::assertSentTimes(SystemMail::class, 1);
    }

    public function test_renewal_reminder_uses_the_next_bill_date(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        Subscription::query()->create([
            'billable_id' => $user->id,
            'billable_type' => $user->getMorphClass(),
            'type' => 'personal',
            'paddle_id' => 'sub_test',
            'status' => 'active',
            'next_billed_at' => now()->addDays(3),
        ]);

        $this->artisan('atlas:mail-renewals')->assertSuccessful();
        $this->artisan('atlas:mail-renewals')->assertSuccessful();

        Notification::assertSentToTimes($user, SystemMail::class, 1);
        Notification::assertSentTo($user, SystemMail::class, fn (SystemMail $mail) => $mail->letter->eyebrow === 'Renewal');
    }
}
