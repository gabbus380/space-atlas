<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Connectors;
use App\Support\Staff;
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
            ->assertSee('Sign in with Apple')
            ->assertSee('Sign in with Google')
            ->assertSee('Sign in with Microsoft')
            ->assertSee('Sign in with LinkedIn')
            ->assertSee('/login/apple', false)
            ->assertSee('/login/google', false);
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

    public function test_an_admin_pastes_google_credentials_and_they_connect(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'staff_role' => Staff::ADMIN])->save();
        $this->actingAs($admin)->get('/admin/sign-in')->assertOk()
            ->assertSee('What each value is')
            ->assertSee('Services ID')
            ->assertSee('.apps.googleusercontent.com', false)
            ->assertSee('/login/google/callback', false);
        $this->post('/admin/sign-in', [
            'providers' => [
                'google' => ['client_id' => 'google-client-id', 'client_secret' => 'google-secret-value'],
            ],
        ])->assertRedirect();
        $this->assertSame('google-client-id', Setting::get('signin.google.client_id'));
        $this->assertSame('google-secret-value', Setting::get('signin.google.client_secret'));
        Connectors::apply();
        $this->assertTrue(Connectors::signInReady('google'));
        $this->assertFalse(Connectors::signInReady('apple'));
    }
}
