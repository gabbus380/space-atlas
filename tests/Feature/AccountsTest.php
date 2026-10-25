<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\TeamInvite;
use App\Notifications\WithdrawalReceived;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        $this->get('/pricing')->assertOk()->assertSee('$19')->assertSee('$228')->assertSee('$4,990')->assertSee('Founding rate')->assertSee('money-back');
        $this->get('/legal')->assertOk();
        $this->get('/withdraw')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/terms')->assertRedirect('/legal#terms');
    }

    public function test_registration_sends_confirmation_and_keeps_checkout_intent(): void
    {
        Notification::fake();
        $this->get('/subscribe/personal?interval=year')->assertRedirect('/login');
        $r = $this->post('/register', [
            'name' => 'Ada Lovelace', 'email' => 'ada@example.org', 'organization' => 'Analytical Engines',
            'password' => 'orbital2026x', 'password_confirmation' => 'orbital2026x', 'terms' => '1',
        ]);
        $r->assertRedirect('/email/verify');
        $u = User::where('email', 'ada@example.org')->first();
        $this->assertNotNull($u->terms_accepted_at);
        $this->assertFalse($u->marketing_opt_in);
        Notification::assertSentTo($u, VerifyEmail::class);
        $this->get('/email/verify')->assertSee('straight to checkout');
    }

    public function test_registration_requires_terms(): void
    {
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.org', 'password' => 'orbital2026x', 'password_confirmation' => 'orbital2026x'])
            ->assertSessionHasErrors('terms');
    }

    public function test_login_and_logout(): void
    {
        $u = User::factory()->create(['password' => 'orbital2026x']);
        $this->post('/login', ['email' => $u->email, 'password' => 'wrong-pass1'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $u->email, 'password' => 'orbital2026x'])->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_checkout_page_without_paddle_keys_explains(): void
    {
        $this->actingAs(User::factory()->create())->get('/subscribe/personal?interval=month')->assertOk()->assertSee('not switched on yet')->assertSee('$29');
    }

    public function test_teams_setup_creates_team(): void
    {
        $u = User::factory()->create(['organization' => 'Orbit Ltd']);
        $this->actingAs($u)->get('/subscribe/teams')->assertOk()->assertSee('Orbit Ltd');
        $this->post('/subscribe/teams', ['name' => 'Orbit Ltd', 'seats' => 4, 'interval' => 'year'])->assertOk()->assertSee('$1,200');
        $this->assertSame($u->fresh()->team_id, Team::where('owner_id', $u->id)->value('id'));
        $this->post('/subscribe/teams', ['name' => 'Orbit Ltd', 'seats' => 2, 'interval' => 'year'])->assertSessionHasErrors('seats');
    }

    public function test_invitation_flow(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Pilot Org', 'owner_id' => $owner->id]);
        $team->forceFill(['override_until' => today()->addDays(30), 'override_seats' => 3])->save();
        $owner->forceFill(['team_id' => $team->id, 'team_joined_at' => now()])->save();

        $this->actingAs($owner)->post('/team/invitations', ['email' => 'Grace@Example.org'])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo(new AnonymousNotifiable, TeamInvite::class, function ($n) use (&$token) { $token = $n->token; return true; });
        $this->post('/team/invitations', ['email' => 'second@example.org']);
        $this->post('/team/invitations', ['email' => 'third@example.org'])->assertSessionHasErrors('email');   // 3 seats used

        $grace = User::factory()->unverified()->create(['email' => 'grace@example.org']);
        $this->actingAs($grace)->get('/invite/'.$token)->assertRedirect('/');
        $grace->refresh();
        $this->assertSame($team->id, $grace->team_id);
        $this->assertTrue($grace->hasVerifiedEmail());
        $this->assertNotNull(TeamInvitation::where('email', 'grace@example.org')->value('accepted_at'));
        $this->get('/invite/'.$token)->assertSee('expired');
    }

    public function test_withdrawal_is_recorded_and_confirmed(): void
    {
        Notification::fake();
        $this->post('/withdraw', ['name' => 'Jan Novak', 'email' => 'jan@example.cz'])->assertOk()->assertSee('We have your withdrawal');
        $this->assertSame(1, Withdrawal::count());
        Notification::assertSentTimes(WithdrawalReceived::class, 2);
    }

    public function test_account_page_and_export(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->get('/account')->assertOk()->assertSee('Free account');
        $this->get('/account/export')->assertOk()->assertJsonPath('account.email', $u->email);
    }

    public function test_admin_is_hidden_from_others(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertNotFound();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Year-one scoreboard');
        $this->post('/admin/api-keys', ['name' => 'Ministry X', 'daily_quota' => 500, 'max_users' => 10])->assertSessionHas('plain_key');
    }
}
