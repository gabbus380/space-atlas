<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use App\Support\OrganizationDirectory;
use App\Support\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_see_only_their_areas(): void
    {
        $sales = $this->staff(Staff::SALES);
        $this->actingAs($sales)->get('/admin')->assertOk()->assertSee('Users');
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/connectors')->assertNotFound();
        $this->get('/admin/api-keys')->assertNotFound();
        $this->get('/admin/directory')->assertNotFound();

        $analyst = $this->staff(Staff::ANALYST);
        $this->actingAs($analyst)->get('/admin/users')->assertNotFound();
        $this->get('/admin/directory?q=Azercosmos')->assertOk()->assertSee('Azercosmos');

        $manager = $this->staff(Staff::MANAGER);
        $this->actingAs($manager)->get('/admin/connectors')->assertNotFound();
        $this->get('/admin/api-keys')->assertOk();
    }

    public function test_coupon_grants_a_free_year(): void
    {
        $admin = $this->staff(Staff::ADMIN);
        $reader = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($admin)->post('/admin/coupons', [
            'code' => 'year-one', 'name' => 'First year', 'months' => 12, 'plan' => 'personal',
        ])->assertRedirect();

        $this->actingAs($reader)->post('/account/coupon', ['code' => 'YEAR-ONE'])->assertRedirect();
        $reader->refresh();
        $this->assertSame('personal', $reader->plan_override);
        $this->assertTrue($reader->override_until->greaterThan(today()->addMonths(11)));
        $this->assertSame(1, Coupon::first()->times_redeemed);
        $this->post('/account/coupon', ['code' => 'YEAR-ONE'])->assertSessionHasErrors('code');
    }

    public function test_a_matched_organization_requires_a_company_email_and_a_title(): void
    {
        $org = OrganizationDirectory::search('Azercosmos')[0];
        $user = User::factory()->create(['email' => 'reader@gmail.com']);

        $this->actingAs($user)->post('/account/profile', [
            'name' => $user->name,
            'organization' => 'Azercosmos',
            'organization_id' => $org['id'],
            'job_title' => 'Analyst',
        ])->assertSessionHasErrors('organization');
        $this->assertNull($user->fresh()->organization_id);

        $user->forceFill(['email' => 'ada@azercosmos.az'])->save();
        $this->post('/account/profile', [
            'name' => 'Ada',
            'organization' => 'Azercosmos',
            'organization_id' => $org['id'],
            'job_title' => 'Analyst',
            'show_on_organization' => '1',
        ])->assertRedirect()->assertSessionHas('status');

        $user->refresh();
        $this->assertSame($org['id'], $user->organization_id);
        $this->assertSame('Analyst', $user->job_title);
        $this->assertTrue($user->show_on_organization);
    }

    public function test_api_key_search_fills_the_organizations_main_account(): void
    {
        $org = OrganizationDirectory::search('Azercosmos')[0];
        $main = User::factory()->create([
            'name' => 'Leyla Aliyeva',
            'email' => 'leyla@azercosmos.az',
            'organization' => 'Azercosmos',
            'organization_id' => $org['id'],
        ]);
        User::factory()->create([
            'email' => 'later@azercosmos.az',
            'organization_id' => $org['id'],
        ]);

        $this->actingAs($this->staff(Staff::ADMIN))
            ->getJson('/admin/api-keys/organizations?q=Azercosmos')
            ->assertOk()
            ->assertJsonPath('0.email', $main->email)
            ->assertJsonPath('0.account', 'Leyla Aliyeva');

        $this->actingAs($this->staff(Staff::SALES))
            ->getJson('/admin/api-keys/organizations?q=Azercosmos')
            ->assertNotFound();
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create();
        $user->assignStaffRole($role);

        return $user;
    }
}
