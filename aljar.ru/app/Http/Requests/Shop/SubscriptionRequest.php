<?php

namespace App\Http\Requests\Shop;

use App\Enums\Grind;
use App\Enums\ProductType;
use App\Models\ProductVariant;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
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
            // Подписка оформляется только на кофе и только на то, что
            // сейчас продаётся: снятый с витрины вариант нельзя возить
            // регулярно.
            'product_variant_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $available = ProductVariant::query()
                        ->whereKey($value)
                        ->where('is_active', true)
                        ->whereHas('product', fn (Builder $query) => $query
                            ->where('type', ProductType::Coffee)
                            ->where('is_hidden', false))
                        ->exists();

                    if (! $available) {
                        $fail('Подписка оформляется только на кофе из каталога.');
                    }
                },
            ],
            'grind' => ['nullable', Rule::enum(Grind::class)],
            'frequency_weeks' => ['required', 'integer', Rule::in(config('subscription.frequencies'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'Выберите кофе и вес.',
            'frequency_weeks.required' => 'Выберите, как часто привозить.',
            'frequency_weeks.in' => 'Такой частоты доставки нет.',
        ];
    }
}
