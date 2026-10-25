<?php

namespace Tests\Feature;

use App\Models\ApiRequest;
use App\Models\User;
use App\Support\Plans;
use App\Support\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_order_creates_a_free_account(): void
    {
        $this->post('/api-order', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@analytical.example',
            'organization' => 'Analytical Engines',
            'job_title' => 'Analyst',
            'password' => 'orbital2026x',
            'password_confirmation' => 'orbital2026x',
            'terms' => '1',
            'message' => 'We will chart national programs inside our tools.',
        ])->assertRedirect('/api-order/received');

        $this->assertAuthenticated();
        $user = User::where('email', 'ada@analytical.example')->first();
        $this->assertNotNull($user);
        $this->assertSame('free', Plans::tier($user));
        $this->assertDatabaseHas('api_requests', [
            'user_id' => $user->id,
            'account_tier' => 'free',
            'created_account' => 1,
            'organization' => 'Analytical Engines',
        ]);
    }

    public function test_a_personal_account_keeps_its_plan(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['plan_override' => 'personal', 'override_until' => today()->addYear()])->save();

        $this->actingAs($user)->post('/api-order', [
            'name' => $user->name,
            'organization' => 'Orbit Ltd',
            'message' => 'Internal dashboards for the strategy team.',
        ])->assertRedirect('/api-order/received');

        $this->assertSame(1, User::count());
        $this->assertDatabaseHas('api_requests', [
            'user_id' => $user->id,
            'account_tier' => 'personal',
            'created_account' => 0,
        ]);
    }

    public function test_sales_can_manage_a_request_and_convert_personal_to_teams(): void
    {
        $reader = User::factory()->create(['organization' => 'Orbit Ltd']);
        $reader->forceFill(['plan_override' => 'personal', 'override_until' => today()->addMonth()])->save();
        $order = ApiRequest::create([
            'user_id' => $reader->id,
            'name' => $reader->name,
            'email' => $reader->email,
            'organization' => 'Orbit Ltd',
            'account_tier' => 'personal',
            'message' => 'Please send the Order Form.',
        ]);

        $sales = User::factory()->create();
        $sales->assignStaffRole(Staff::SALES);

        $this->actingAs($sales)->get('/admin/api-requests')->assertOk()->assertSee('Orbit Ltd');
        $this->post('/admin/api-requests/'.$order->id, ['status' => 'reviewing', 'staff_note' => 'Call Tuesday'])
            ->assertRedirect();
        $this->assertSame('reviewing', $order->fresh()->status);

        $this->post('/admin/users/'.$reader->id.'/convert-team', [
            'name' => 'Orbit Ltd', 'seats' => 3, 'days' => 30,
        ])->assertRedirect();
        $this->assertSame('teams', Plans::tier($reader->fresh()));

        $analyst = User::factory()->create();
        $analyst->assignStaffRole(Staff::ANALYST);
        $this->actingAs($analyst)->get('/admin/api-requests')->assertNotFound();
    }
}
