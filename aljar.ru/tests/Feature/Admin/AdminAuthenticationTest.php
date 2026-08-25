<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_can_be_rendered()
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
    }

    public function test_admins_can_authenticate_using_the_admin_login_screen()
    {
        $admin = Admin::factory()->create();

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin, 'admin');
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_admins_can_not_authenticate_with_invalid_password()
    {
        $admin = Admin::factory()->create();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('admin');
    }

    public function test_deactivated_admins_can_not_authenticate()
    {
        $admin = Admin::factory()->inactive()->create();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertGuest('admin');
    }

    public function test_customer_credentials_do_not_work_on_the_admin_login_screen()
    {
        $customer = Customer::factory()->create();

        $this->post(route('admin.login.store'), [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertGuest('admin');
        $this->assertGuest();
    }

    public function test_admins_can_logout()
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_guests_are_redirected_to_the_admin_login_screen()
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_customers_can_not_reach_the_admin_panel()
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer)->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admins_can_not_reach_the_customer_dashboard()
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
