<?php

namespace App\Http\Requests\Shop;

use App\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WholesaleLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'company' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'business_type' => ['required', Rule::enum(BusinessType::class)],
            'volume' => ['nullable', 'string', 'max:160'],
            'comment' => ['nullable', 'string', 'max:1000'],
            // Спецификация или бриф: по ТЗ 5.4 поле необязательное.
            'file' => ['nullable', 'file', 'max:8192', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
            'agreement' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Укажите телефон — по нему свяжется менеджер.',
            'company.required' => 'Укажите компанию: заявка идёт в отдел продаж.',
            'agreement.accepted' => 'Согласие на обработку данных обязательно.',
            'file.max' => 'Файл больше 8 МБ — пришлите ссылку в комментарии.',
        ];
    }
}
