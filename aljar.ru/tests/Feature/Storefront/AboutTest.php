<?php

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AboutTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_opens_for_a_guest()
    {
        $this->get(route('info.about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('info/About'));
    }
}
