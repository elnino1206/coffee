<?php

namespace Tests\Feature\Shop;

use App\Enums\BusinessType;
use App\Enums\LeadStatus;
use App\Models\Admin;
use App\Models\WholesaleLead;
use App\Notifications\WholesaleLeadReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WholesaleLeadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function fields(array $overrides = []): array
    {
        return [
            'name' => 'Сергей Ватагин',
            'company' => 'Кофейня «Полдень»',
            'phone' => '+7 903 771-20-40',
            'email' => 's.vatagin@polden.ru',
            'business_type' => 'cafe',
            'volume' => '20 кг/мес, эспрессо-смесь',
            'comment' => 'Нужен стабильный профиль под рожок.',
            'agreement' => '1',
            ...$overrides,
        ];
    }

    public function test_form_offers_every_business_type()
    {
        $this->get(route('info.wholesale'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('info/Wholesale')
                ->has('businessTypes', count(BusinessType::cases()))
                ->where('businessTypes.0.label', 'Кофейня'),
            );
    }

    public function test_lead_is_saved_and_gets_its_own_number()
    {
        Notification::fake();

        $this->post(route('info.wholesale.store'), $this->fields())->assertRedirect();

        $lead = WholesaleLead::query()->firstOrFail();

        $this->assertStringStartsWith('B2B-', $lead->number);
        $this->assertSame('Кофейня «Полдень»', $lead->company);
        $this->assertSame(BusinessType::Cafe, $lead->business_type);
        $this->assertSame(LeadStatus::New, $lead->status);
        // Согласие фиксируется моментом: доказательством служит время.
        $this->assertNotNull($lead->consent_at);
    }

    public function test_number_of_the_accepted_lead_comes_back_to_the_page()
    {
        Notification::fake();

        $this->post(route('info.wholesale.store'), $this->fields());

        $number = WholesaleLead::query()->value('number');

        $this->get(route('info.wholesale'))
            ->assertInertia(fn ($page) => $page->where('accepted', $number));
    }

    public function test_attachment_goes_to_private_storage()
    {
        Notification::fake();
        Storage::fake();

        $this->post(route('info.wholesale.store'), $this->fields([
            'file' => UploadedFile::fake()->create('спецификация.pdf', 20, 'application/pdf'),
        ]))->assertRedirect();

        $lead = WholesaleLead::query()->firstOrFail();

        $this->assertNotNull($lead->attachment_path);
        $this->assertSame('спецификация.pdf', $lead->attachment_name);
        Storage::assertExists($lead->attachment_path);
    }

    public function test_managers_who_asked_for_leads_are_notified()
    {
        Notification::fake();

        $wanted = Admin::factory()->create(['notify_leads' => true]);
        $silent = Admin::factory()->create(['notify_leads' => false]);
        $fired = Admin::factory()->create(['notify_leads' => true, 'is_active' => false]);

        $this->post(route('info.wholesale.store'), $this->fields());

        Notification::assertSentTo($wanted, WholesaleLeadReceived::class);
        Notification::assertNotSentTo($silent, WholesaleLeadReceived::class);
        Notification::assertNotSentTo($fired, WholesaleLeadReceived::class);
    }

    public function test_lead_without_consent_is_rejected()
    {
        Notification::fake();

        $this->post(route('info.wholesale.store'), $this->fields(['agreement' => null]))
            ->assertSessionHasErrors('agreement');

        $this->assertDatabaseCount('wholesale_leads', 0);
    }

    public function test_company_and_phone_are_required()
    {
        $this->post(route('info.wholesale.store'), $this->fields(['company' => '', 'phone' => '']))
            ->assertSessionHasErrors(['company', 'phone']);
    }
}
