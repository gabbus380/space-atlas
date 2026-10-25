<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_offers_apple_google_microsoft_and_linkedin(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('/login/apple', false)
            ->assertSee('/login/google', false)
            ->assertSee('/login/microsoft', false)
            ->assertSee('/login/linkedin', false)
            ->assertSee('Apple ID on your iCloud account');
    }

    public function test_an_unconnected_provider_explains_itself(): void
    {
        $this->get('/login/google')->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_google_creates_a_verified_account_and_signs_in(): void
    {
        config([
            'services.google.client_id' => 'client',
            'services.google.client_secret' => 'secret',
            'services.google.redirect' => 'http://localhost/login/google/callback',
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-1',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]));

        $this->get('/login/google/callback')->assertRedirect('/');
        $this->assertAuthenticated();
        $user = User::query()->where('email', 'ada@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('google', $user->auth_provider);
        $this->assertSame('google-1', $user->auth_provider_id);
        $this->assertNull($user->password);
    }

    public function test_google_signs_in_an_existing_email(): void
    {
        $existing = User::factory()->create(['email' => 'ada@example.com']);
        config([
            'services.google.client_id' => 'client',
            'services.google.client_secret' => 'secret',
            'services.google.redirect' => 'http://localhost/login/google/callback',
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-9',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]));

        $this->get('/login/google/callback')->assertRedirect('/');
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google', $existing->fresh()->auth_provider);
    }
}
