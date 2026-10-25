<?php

namespace Tests\Feature;

use App\Models\FileUnlock;
use App\Models\Team;
use App\Models\User;
use App\Support\AtlasData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

class AtlasAccessTest extends TestCase
{
    use RefreshDatabase;

    private function ent(string $html): array
    {
        $this->assertSame(1, preg_match('/window\.ATLAS_ENT=(\{.*?\});<\/script>/s', $html, $m), 'ATLAS_ENT missing');

        return json_decode($m[1], true);
    }

    private function embeddedAtlas(string $html): array
    {
        $data = \App\Support\OpenAtlas::embedded($html);
        $this->assertIsArray($data);

        return $data;
    }

    private function personal(): User
    {
        $u = User::factory()->create();
        $s = Subscription::forceCreate(['billable_type' => $u->getMorphClass(), 'billable_id' => $u->id, 'type' => 'personal', 'paddle_id' => 'sub_test_'.$u->id, 'status' => 'active']);
        $s->items()->forceCreate(['product_id' => 'pro_1', 'price_id' => 'pri_py', 'status' => 'active', 'quantity' => 1]);

        return $u;
    }

    private function team(int $seats = 3): array
    {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Acme Space', 'owner_id' => $owner->id]);
        $owner->forceFill(['team_id' => $team->id, 'team_joined_at' => now()->subDay()])->save();
        $s = Subscription::forceCreate(['billable_type' => $owner->getMorphClass(), 'billable_id' => $owner->id, 'type' => 'teams', 'paddle_id' => 'sub_team_'.$owner->id, 'status' => 'active']);
        $s->items()->forceCreate(['product_id' => 'pro_2', 'price_id' => 'pri_ty', 'status' => 'active', 'quantity' => $seats]);
        $member = User::factory()->create(['team_id' => $team->id]);
        $member->forceFill(['team_id' => $team->id, 'team_joined_at' => now()])->save();

        return [$owner, $member, $team];
    }

    public function test_guest_gets_open_edition_without_cookies(): void
    {
        $r = $this->get('/');
        $r->assertOk();
        $this->assertSame([], $r->headers->getCookies(), 'the open atlas must set no cookies');
        $html = $r->getContent();
        $this->assertSame('guest', $this->ent($html)['tier']);
        $this->assertStringContainsString('"projects":[]', $html);
        $atlas = app(AtlasData::class)->atlas();
        $this->assertStringNotContainsString($atlas['blackhat'][0]['title'], $html);
        $this->assertStringNotContainsString('"cm25":3600', $html);
        $atlasJson = $this->embeddedAtlas($html);
        $this->assertCount(18, $atlasJson['countries']);
        $this->assertNotContains('NLD', array_column($atlasJson['countries'], 'iso3'));
        $this->assertStringContainsString('Compare countries side by side.', $html);
    }

    public function test_guest_live_data_is_filtered(): void
    {
        $r = $this->getJson('/app/live');
        $r->assertOk();
        $this->assertSame([], $r->headers->getCookies());
        $j = $r->json();
        $this->assertCount(1, $j['collections']['briefings']);
        $b = array_values($j['collections']['briefings'])[0];
        $this->assertTrue($b['locked']);
        $this->assertArrayNotHasKey('global', $b);
        $this->assertArrayNotHasKey('audio', $b);
        foreach ($j['collections']['country'] as $doc) {
            $this->assertTrue($doc['locked']);
            $this->assertArrayNotHasKey('stakeholders', $doc);
            $this->assertArrayNotHasKey('summary', (array) $doc['strategy']);
        }
        $this->assertArrayNotHasKey('config', $j['collections']);
        $this->assertSame('guest', $j['ent']['tier']);
    }

    public function test_free_account_opens_three_files_a_month(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        foreach (['DEU', 'ITA', 'GBR'] as $iso) {
            $this->postJson("/app/unlock/$iso")->assertOk();
        }
        $this->postJson('/app/unlock/DEU')->assertOk();                       // already open, no charge
        $this->postJson('/app/unlock/FRA')->assertStatus(409);
        $this->assertSame(3, FileUnlock::where('user_id', $u->id)->count());
        $c = $this->getJson('/app/live')->json('collections.country');
        $this->assertArrayNotHasKey('locked', $c['DEU']);
        $this->assertArrayHasKey('stakeholders', $c['DEU']);
        $this->assertTrue($c['FRA']['locked']);
        $ent = $this->ent($this->get('/')->getContent());
        $this->assertSame('free', $ent['tier']);
        $this->assertSame(0, $ent['credits']['left']);
    }

    public function test_unverified_account_cannot_unlock(): void
    {
        $this->actingAs(User::factory()->unverified()->create());
        $this->postJson('/app/unlock/DEU')->assertStatus(403);
    }

    public function test_personal_gets_full_edition_and_saves_watchlist(): void
    {
        $u = $this->personal();
        $this->actingAs($u);
        $html = $this->get('/')->getContent();
        $ent = $this->ent($html);
        $this->assertSame('personal', $ent['tier']);
        $this->assertFalse($ent['features']['csv']);
        $atlas = app(AtlasData::class)->atlas();
        $this->assertStringContainsString($atlas['blackhat'][0]['title'], $html);
        $this->assertStringContainsString('"cm25":3600', $html);

        $j = $this->getJson('/app/live')->json();
        $this->assertGreaterThan(1, count($j['collections']['briefings']));
        $this->assertArrayHasKey('global', array_values($j['collections']['briefings'])[0]);

        $this->putJson('/app/watchlist', ['iso3' => ['deu', 'ITA', 'GBR']])->assertOk()->assertJsonPath('doc.iso3', ['DEU', 'ITA', 'GBR']);
        $this->assertSame(['DEU', 'ITA', 'GBR'], $this->getJson('/app/live')->json('collections.config.focus.iso3'));
        $this->putJson('/app/notes/DEU', ['text' => 'x'])->assertStatus(402);
    }

    public function test_free_cannot_save_watchlist_to_account(): void
    {
        $this->actingAs(User::factory()->create());
        $this->putJson('/app/watchlist', ['iso3' => ['DEU']])->assertStatus(402);
    }

    public function test_teams_member_shares_notes_and_team_watchlist(): void
    {
        [$owner, $member, $team] = $this->team();
        $this->actingAs($member);
        $ent = $this->ent($this->get('/')->getContent());
        $this->assertSame('teams', $ent['tier']);
        $this->assertTrue($ent['features']['csv']);
        $this->putJson('/app/notes/POL', ['text' => 'Ministry reshuffle expected after the budget.'])->assertOk()->assertJsonPath('doc.iso3', 'POL');
        $this->postJson('/app/watchlist/scope', ['scope' => 'team'])->assertOk();
        $this->putJson('/app/watchlist', ['iso3' => ['POL', 'CZE']])->assertOk();

        $this->actingAs($owner);
        $this->postJson('/app/watchlist/scope', ['scope' => 'team'])->assertOk();
        $j = $this->getJson('/app/live')->json('collections');
        $this->assertSame('Ministry reshuffle expected after the budget.', $j['notes']['POL']['text']);
        $this->assertSame(['POL', 'CZE'], $j['config']['focus']['iso3']);
        $this->assertSame(['POL', 'CZE'], $team->fresh()->watchlist);
    }

    public function test_seat_order_decides_who_keeps_access(): void
    {
        [$owner, $member, $team] = $this->team(2);
        $third = User::factory()->create();
        $third->forceFill(['team_id' => $team->id, 'team_joined_at' => now()->addMinute()])->save();
        $this->actingAs($third);
        $this->assertSame('free', $this->ent($this->get('/')->getContent())['tier']);
        $this->actingAs($member);
        $this->assertSame('teams', $this->ent($this->get('/')->getContent())['tier']);
    }

    public function test_canceled_plan_runs_to_period_end(): void
    {
        $u = $this->personal();
        $u->subscription('personal')->forceFill(['status' => 'active', 'ends_at' => now()->addDays(5)])->save();
        $this->actingAs($u);
        $this->assertSame('personal', $this->ent($this->get('/')->getContent())['tier']);
        $u->subscription('personal')->forceFill(['status' => 'canceled', 'ends_at' => now()->subDay()])->save();
        $this->assertSame('free', $this->ent($this->actingAs($u->fresh())->get('/')->getContent())['tier']);
    }

    public function test_audio_is_for_paid_plans(): void
    {
        $file = collect(scandir(config('atlas.audio_path')))->first(fn ($f) => str_ends_with($f, '.mp4'));
        $this->assertNotNull($file);
        $this->get("/app/audio/$file")->assertStatus(403);
        $this->actingAs(User::factory()->create())->get("/app/audio/$file")->assertStatus(403);
        $this->actingAs($this->personal())->get("/app/audio/$file")->assertOk();
        $this->get('/app/audio/..%2F.env')->assertStatus(404);
    }

    public function test_complimentary_personal(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['plan_override' => 'personal', 'override_until' => today()->addDays(3)])->save();
        $this->actingAs($u);
        $this->assertSame('personal', $this->ent($this->get('/')->getContent())['tier']);
    }

    public function test_team_pilot_without_subscription(): void
    {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Pilot Org', 'owner_id' => $owner->id]);
        $team->forceFill(['override_until' => today()->addDays(30), 'override_seats' => 5])->save();
        $owner->forceFill(['team_id' => $team->id, 'team_joined_at' => now()])->save();
        $this->actingAs($owner);
        $this->assertSame('teams', $this->ent($this->get('/')->getContent())['tier']);
        $this->assertSame(5, $team->fresh()->seats());
    }

    public function test_plans_and_checkout_name_emma_and_paddle(): void
    {
        $this->get('/pricing')->assertOk()
            ->assertSee('/brand/paddle.svg', false)
            ->assertSee('EMMA Aerospace Consulting LLP')
            ->assertSee('border-radius:0', false);
        $this->actingAs(User::factory()->create())
            ->get('/subscribe/personal?interval=year')
            ->assertOk()
            ->assertSee('/brand/paddle.svg', false)
            ->assertSee('Merchant of record')
            ->assertSee('border-radius:0', false);
    }
}
