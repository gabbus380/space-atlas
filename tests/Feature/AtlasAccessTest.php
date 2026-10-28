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
        $this->assertGreaterThan(100, count($atlasJson['countries']));
        $this->assertContains('NLD', array_column($atlasJson['countries'], 'iso3'));
        $fra = collect($atlasJson['countries'])->firstWhere('iso3', 'FRA');
        $this->assertNotNull($fra);
        $this->assertArrayHasKey('space', $fra);
        $this->assertArrayNotHasKey('gdp', $fra);
        $this->assertArrayNotHasKey('cm25', $fra['esa']);
        $this->assertStringContainsString('Compare countries side by side.', $html);
        $this->assertStringContainsString('id="cmpStage"', $html);
        $this->assertStringContainsString('class="cmp-data" hidden', $html);
    }

    public function test_guest_live_data_is_filtered(): void
    {
        $r = $this->getJson('/app/live');
        $r->assertOk();
        $this->assertSame([], $r->headers->getCookies());
        $j = $r->json();
        $this->assertCount(1, $j['collections']['briefings']);
        $b = array_values($j['collections']['briefings'])[0];
        $this->assertTrue($b['demo']);
        $this->assertArrayHasKey('global', $b);
        $this->assertArrayNotHasKey('locked', $b);
        foreach ($j['collections']['country'] as $doc) {
            $this->assertTrue($doc['locked']);
            $this->assertArrayNotHasKey('stakeholders', $doc);
            $this->assertArrayNotHasKey('summary', (array) $doc['strategy']);
        }
        $this->assertArrayNotHasKey('config', $j['collections']);
        $this->assertSame('guest', $j['ent']['tier']);
    }

    public function test_worldwide_country_files_are_complete_and_honestly_labelled(): void
    {
        $atlas = app(AtlasData::class)->atlas();
        $files = $atlas['cfiles'];
        $liveFiles = app(AtlasData::class)->live()['collections']['country'];

        $this->assertCount(235, $files);
        $this->assertSame(array_keys($files), array_keys($liveFiles));
        $this->assertSame(
            collect($atlas['countries'])->pluck('iso3')->sort()->values()->all(),
            collect(array_keys($files))->sort()->values()->all(),
        );
        $this->assertSame(62, collect($files)->where('verify', 'verified')->count());
        $this->assertSame(173, collect($files)->where('verify', 'partial')->count());
        $this->assertSame(235, collect($files)->filter(fn ($file) => isset($file['media']))->count());
        $this->assertNotEmpty($files['HUN']['media']['outlets']);
        $this->assertNotEmpty($files['GBR']['media']['outlets']);

        foreach ($files as $iso3 => $file) {
            $this->assertSame($iso3, $file['iso3']);
            $this->assertContains($file['verify'], ['verified', 'partial']);
        }
    }

    public function test_open_edition_has_all_country_stubs_without_full_or_media_data(): void
    {
        $atlas = $this->embeddedAtlas($this->get('/')->getContent());

        $this->assertCount(235, $atlas['cfiles']);
        foreach ($atlas['cfiles'] as $file) {
            $this->assertTrue($file['locked']);
            $this->assertArrayNotHasKey('stakeholders', $file);
            $this->assertArrayNotHasKey('media', $file);
        }
    }

    public function test_paid_edition_includes_published_local_media(): void
    {
        $this->actingAs($this->personal());
        $response = $this->get('/');
        $response->assertOk()->assertSee('Local media');
        $atlas = $this->embeddedAtlas($response->getContent());

        $this->assertCount(235, $atlas['cfiles']);
        $this->assertNotEmpty($atlas['cfiles']['AFG']['media']['outlets']);
        $this->assertArrayHasKey('stakeholders', $atlas['cfiles']['AFG']);
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
        $this->assertStringContainsString('id="cmpStage"', $html);
        $this->assertStringContainsString('European Space Agency', $html);

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

    public function test_team_subscription_watchlist_stays_separate_from_the_personal_list(): void
    {
        [$owner, $member, $team] = $this->team();
        $owner->forceFill(['watchlist' => ['USA', 'FRA'], 'watch_scope' => 'me'])->save();
        $this->actingAs($owner);

        $mine = $this->getJson('/app/live')->json('collections.config.focus');
        $this->assertSame('me', $mine['scope']);
        $this->assertTrue($mine['saved']);
        $this->assertSame(['USA', 'FRA'], $mine['iso3']);

        $this->postJson('/app/watchlist/scope', ['scope' => 'team'])->assertOk()
            ->assertJsonPath('ent.watchScope', 'team')
            ->assertJsonPath('ent.features.watch', 'team');
        $teamList = $this->getJson('/app/live')->json('collections.config.focus');
        $this->assertSame('team', $teamList['scope']);
        $this->assertFalse($teamList['saved']);
        $this->assertSame([], $teamList['iso3']);

        $this->putJson('/app/watchlist', ['iso3' => ['DEU', 'IND', 'CHN']])->assertOk()
            ->assertJsonPath('doc.scope', 'team')
            ->assertJsonPath('doc.iso3', ['DEU', 'IND', 'CHN']);
        $this->assertSame(['DEU', 'IND', 'CHN'], $team->fresh()->watchlist);
        $this->assertSame(['USA', 'FRA'], $owner->fresh()->watchlist);

        $this->postJson('/app/watchlist/scope', ['scope' => 'me'])->assertOk();
        $this->assertSame(['USA', 'FRA'], $this->getJson('/app/live')->json('collections.config.focus.iso3'));

        $this->actingAs($member);
        $this->postJson('/app/watchlist/scope', ['scope' => 'team'])->assertOk();
        $shared = $this->getJson('/app/live')->json('collections.config.focus');
        $this->assertSame('team', $shared['scope']);
        $this->assertSame(['DEU', 'IND', 'CHN'], $shared['iso3']);
    }

    public function test_default_watchlist_names_the_six_reference_countries(): void
    {
        $needle = 'const FOCUS_DEFAULT = ["USA", "GBR", "DEU", "FRA", "IND", "CHN"]';
        $this->get('/')->assertOk()->assertSee($needle, false);
        $this->actingAs($this->personal())->get('/')->assertOk()->assertSee($needle, false);
        $this->get('/')->assertSee('scope === "team"', false);
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
        $demo = app(AtlasData::class)->demoAudioFile();
        $this->get('/app/audio/brief_2000-01-01.mp4')->assertStatus(403);
        if ($demo && is_file(config('atlas.audio_path').'/'.$demo)) {
            $this->get('/app/audio/'.$demo)->assertOk();
        }
        $this->actingAs(User::factory()->create())->get('/app/audio/brief_2000-01-01.mp4')->assertStatus(403);
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
