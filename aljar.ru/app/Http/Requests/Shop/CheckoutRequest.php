<?php

namespace App\Http\Requests\Shop;

use App\Enums\PayMethod;
use App\Enums\ShipMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'contact_name' => ['nullable', 'string', 'max:120'],
            // Телефон — основной способ связи: по нему подтверждают заказ.
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'ship_method' => ['required', Rule::enum(ShipMethod::class)],
            'address' => [
                Rule::requiredIf(fn () => ShipMethod::tryFrom((string) $this->input('ship_method'))?->requiresAddress() === true),
                'nullable', 'string', 'max:255',
            ],
            'pay_method' => ['required', Rule::enum(PayMethod::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
            'agreement' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Укажите телефон — мы подтвердим заказ по SMS.',
            'address.required' => 'Укажите адрес: курьеру нужно знать, куда ехать.',
            'agreement.accepted' => 'Согласие с офертой обязательно.',
        ];
    }
}
