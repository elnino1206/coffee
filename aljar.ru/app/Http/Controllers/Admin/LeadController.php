<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\WholesaleLead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Оптовые заявки в админке.
 *
 * Поля заявки не правятся: это слова клиента, и менять их задним числом
 * нельзя. Менеджер двигает статус и пишет свой комментарий — что
 * обещано, о чём договорились.
 */
class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
        ]);

        $leads = WholesaleLead::query()
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $needle = '%'.mb_strtolower($term).'%';

                $query->where(fn (Builder $where) => $where
                    ->whereRaw('lower(number) like ?', [$needle])
                    ->orWhereRaw('lower(name) like ?', [$needle])
                    ->orWhereRaw('lower(company) like ?', [$needle])
                    ->orWhereRaw('lower(phone) like ?', [$needle]));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Leads', [
            'leads' => $leads->through(fn (WholesaleLead $lead) => $this->row($lead)),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statuses' => collect(LeadStatus::cases())->map(fn (LeadStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }

    public function update(Request $request, WholesaleLead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'manager_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $lead->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Заявка {$lead->number} обновлена."]);

        return back();
    }

    /**
     * Файл заявки лежит в закрытом хранилище и отдаётся только админу:
     * спецификация клиента — не публичный документ.
     */
    public function attachment(WholesaleLead $lead): StreamedResponse
    {
        abort_if($lead->attachment_path === null, 404);

        return Storage::download($lead->attachment_path, $lead->attachment_name ?? basename($lead->attachment_path));
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(WholesaleLead $lead): array
    {
        return [
            'id' => $lead->id,
            'number' => $lead->number,
            'received_at' => $lead->receivedAtLabel(),
            'name' => $lead->name,
            'company' => $lead->company,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'business_label' => $lead->business_type->label(),
            'volume' => $lead->volume,
            'comment' => $lead->comment,
            'manager_comment' => $lead->manager_comment,
            'attachment_name' => $lead->attachment_name,
            'status' => $lead->status->value,
            'status_label' => $lead->status->label(),
        ];
    }
}
