<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_a_key(): void
    {
        $this->getJson('/v1/countries')->assertStatus(401);
        $this->getJson('/v1/countries', ['Authorization' => 'Bearer nope'])->assertStatus(401);
    }

    public function test_endpoints(): void
    {
        [, $key] = ApiKey::issue(['name' => 'Test', 'daily_quota' => 1000, 'max_users' => 10]);
        $h = ['Authorization' => 'Bearer '.$key];
        $this->getJson('/v1/countries?limit=500', $h)->assertOk()->assertJsonPath('meta.total', 235)->assertHeader('X-Quota-Limit', '1000');
        $this->getJson('/v1/countries/DEU/file', $h)->assertOk()->assertJsonPath('data.iso3', 'DEU');
        $this->getJson('/v1/countries/XXX/file', $h)->assertStatus(404);
        $this->getJson('/v1/programs', $h)->assertOk()->assertJsonPath('meta.total', 49);
        $this->getJson('/v1/organizations?iso3=HUN', $h)->assertOk();
        $e = $this->getJson('/v1/engagements?iso3=DEU', $h)->assertOk()->json('data');
        $this->assertNotEmpty($e);
        $this->assertArrayHasKey('company', $e[0]);
        $this->getJson('/v1/briefings/latest', $h)->assertOk()->assertJsonMissingPath('data.audio');
        $this->getJson('/v1/events?type=congress', $h)->assertOk();
    }

    public function test_quota(): void
    {
        [, $key] = ApiKey::issue(['name' => 'Tiny', 'daily_quota' => 100, 'max_users' => 1]);
        $k = ApiKey::first();
        $k->usage()->create(['day' => now('UTC')->toDateString(), 'count' => 100]);
        $this->getJson('/v1/programs', ['Authorization' => 'Bearer '.$key])->assertStatus(429)->assertJsonPath('error.code', 'quota_exceeded');
    }
}
