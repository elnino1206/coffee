<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\WholesaleLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeadsTest extends TestCase
{
    use RefreshDatabase;

    protected function lead(array $attributes = []): WholesaleLead
    {
        return WholesaleLead::query()->create([
            'number' => 'B2B-'.fake()->unique()->numberBetween(40, 999),
            'name' => 'Сергей Ватагин',
            'company' => 'Кофейня «Полдень»',
            'phone' => '+7 903 771-20-40',
            'email' => 's.vatagin@polden.ru',
            'business_type' => 'cafe',
            'volume' => '20 кг/мес',
            'status' => LeadStatus::New,
            'consent_at' => now(),
            ...$attributes,
        ]);
    }

    public function test_leads_are_closed_to_customers()
    {
        $this->actingAs(Customer::factory()->create())
            ->get(route('admin.leads.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_list_shows_leads()
    {
        $lead = $this->lead();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leads.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Leads')
                ->where('leads.data.0.number', $lead->number)
                ->where('leads.data.0.company', 'Кофейня «Полдень»')
                ->where('leads.data.0.business_label', 'Кофейня'),
            );
    }

    public function test_leads_can_be_filtered_by_status_and_found_by_company()
    {
        $this->lead(['company' => 'Отель «Гранат»', 'status' => LeadStatus::Work]);
        $new = $this->lead();

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leads.index', ['status' => 'new']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.number', $new->number),
            );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leads.index', ['q' => 'гранат']))
            ->assertInertia(fn (Assert $page) => $page->has('leads.data', 1));
    }

    public function test_manager_moves_the_status_and_leaves_a_comment()
    {
        $lead = $this->lead();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->patch(route('admin.leads.update', $lead), [
                'status' => 'work',
                'manager_comment' => 'Выслали прайс, ждём ответ до пятницы.',
            ])
            ->assertRedirect();

        $lead->refresh();

        $this->assertSame(LeadStatus::Work, $lead->status);
        $this->assertSame('Выслали прайс, ждём ответ до пятницы.', $lead->manager_comment);
    }

    public function test_client_words_are_not_editable()
    {
        $lead = $this->lead();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->patch(route('admin.leads.update', $lead), [
                'status' => 'work',
                // Попытка переписать заявку мимо формы.
                'company' => 'Другая компания',
                'comment' => 'Другой текст',
            ]);

        $lead->refresh();

        $this->assertSame('Кофейня «Полдень»', $lead->company);
        $this->assertNull($lead->comment);
    }

    public function test_attachment_is_given_only_to_an_admin()
    {
        Storage::fake();

        $path = UploadedFile::fake()->create('бриф.pdf', 10, 'application/pdf')->store('leads');
        $lead = $this->lead(['attachment_path' => $path, 'attachment_name' => 'бриф.pdf']);

        $this->get(route('admin.leads.attachment', $lead))->assertRedirect(route('admin.login'));

        // Имя файла кириллическое, поэтому проверяется utf-8-часть
        // заголовка: ASCII-запасной вариант браузеру отдаётся в
        // транслите, и сравнивать с ним нечего.
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leads.attachment', $lead))
            ->assertOk()
            ->assertHeaderMissing('X-Inertia')
            ->assertHeader(
                'content-disposition',
                "attachment; filename=brif.pdf; filename*=utf-8''".rawurlencode('бриф.pdf'),
            );
    }

    public function test_new_leads_are_counted_in_the_menu()
    {
        $this->lead();
        $this->lead(['status' => LeadStatus::Done]);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('adminCounts.leads', 1));
    }
}
