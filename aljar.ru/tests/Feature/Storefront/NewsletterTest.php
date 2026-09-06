<?php

namespace Tests\Feature\Storefront;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_subscribe()
    {
        $this->post(route('newsletter.store'), [
            'email' => 'Masha@Example.RU',
            'source' => 'home',
        ])->assertRedirect();

        // Адрес приводится к нижнему регистру: иначе один человек с двумя
        // написаниями попадёт в базу дважды.
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'masha@example.ru',
            'source' => 'home',
        ]);
    }

    public function test_subscribing_twice_does_not_duplicate_the_address()
    {
        $this->post(route('newsletter.store'), ['email' => 'masha@example.ru']);
        $this->post(route('newsletter.store'), ['email' => 'masha@example.ru']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_unsubscribed_address_comes_back_without_losing_its_record()
    {
        $subscriber = NewsletterSubscriber::query()->create([
            'email' => 'masha@example.ru',
            'unsubscribed_at' => now()->subMonth(),
        ]);

        $this->post(route('newsletter.store'), ['email' => 'masha@example.ru']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertNull($subscriber->refresh()->unsubscribed_at);
    }

    public function test_a_broken_address_is_rejected()
    {
        $this->post(route('newsletter.store'), ['email' => 'не почта'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}
