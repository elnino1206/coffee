<?php

namespace App\Http\Controllers\Shop;

use App\Enums\BusinessType;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\WholesaleLeadRequest;
use App\Models\Admin;
use App\Models\NumberSequence;
use App\Models\WholesaleLead;
use App\Notifications\WholesaleLeadReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Приём оптовых заявок.
 *
 * Заявка сохраняется и только потом рассылается: если уведомление не
 * ушло, она всё равно лежит в базе и её разберут в панели. Обратный
 * порядок терял бы заявки при любой заминке с почтой.
 */
class WholesaleLeadController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('info/Wholesale', [
            // Номер принятой заявки живёт во флеш-сессии один запрос:
            // человеку нужно, на что сослаться при звонке.
            'accepted' => $request->session()->get('lead'),
            'businessTypes' => collect(BusinessType::cases())->map(fn (BusinessType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function store(WholesaleLeadRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $attachment = $request->file('file');

        $lead = WholesaleLead::query()->create([
            'number' => NumberSequence::next('lead'),
            'name' => $data['name'],
            'company' => $data['company'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'business_type' => $data['business_type'],
            'volume' => $data['volume'] ?? null,
            'comment' => $data['comment'] ?? null,
            // Файлы заявок лежат в закрытом хранилище: спецификация — не
            // публичный документ, и раздавать её ссылкой нельзя.
            'attachment_path' => $attachment?->store('leads'),
            'attachment_name' => $attachment?->getClientOriginalName(),
            'status' => LeadStatus::New,
            'consent_at' => now(),
        ]);

        Notification::send(
            Admin::query()->where('is_active', true)->where('notify_leads', true)->get(),
            new WholesaleLeadReceived($lead),
        );

        return back()->with('lead', $lead->number);
    }
}
