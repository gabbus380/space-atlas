<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_refreshing_a_key_retires_the_previous_secret(): void
    {
        [$key, $plain] = ApiKey::issue([
            'name' => 'Orbit Ltd',
            'contact_email' => 'ada@orbit.example',
            'daily_quota' => 1000,
            'max_users' => 5,
        ]);
        $owner = User::factory()->create(['email' => 'ada@orbit.example']);
        $other = User::factory()->create();

        $this->actingAs($other)->get('/account/api')->assertNotFound();
        $this->post('/account/api-keys/'.$key->id.'/refresh')->assertNotFound();

        $this->actingAs($owner)->get('/account')->assertOk()->assertSee('API documentation');
        $this->get('/account/api')->assertOk()->assertSee('GET /countries')->assertSee('/v1');
        $this->post('/account/api-keys/'.$key->id.'/refresh')->assertRedirect('/account/api')->assertSessionHas('plain_key');

        $fresh = $key->fresh();
        $this->assertNull(ApiKey::findByPlain($plain));
        $this->assertNotSame($plain, session('plain_key'));
        $this->assertSame($fresh->id, ApiKey::findByPlain(session('plain_key'))->id);
    }
}
