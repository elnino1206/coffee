<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_old_dashboard_address_leads_to_the_account()
    {
        // Стартовый набор уводил после входа на /dashboard. Кабинет теперь
        // один и живёт по /account, прежний адрес остаётся ссылкой на него.
        $this->actingAs(Customer::factory()->create())
            ->get('/dashboard')
            ->assertRedirect('/account');
    }
}
