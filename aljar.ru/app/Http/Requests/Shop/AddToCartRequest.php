<?php

namespace App\Http\Requests\Shop;

use App\Enums\Grind;
use App\Enums\Roast;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'grind' => ['nullable', 'string', 'in:'.implode(',', array_column(Grind::cases(), 'value'))],
            'roast' => ['nullable', 'string', 'in:'.implode(',', array_column(Roast::cases(), 'value'))],
            'subscribe' => ['boolean'],
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * Помол обязателен для кофе и бессмыслен для всего остального.
     * Проверка идёт после основных правил: до неё вариант может не
     * существовать вовсе.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $variant = ProductVariant::query()->with('product')->find($this->integer('product_variant_id'));
                $type = $variant?->product?->type;

                if ($type === null) {
                    return;
                }

                if ($type->requiresGrind() && $this->input('grind') === null) {
                    $validator->errors()->add('grind', 'Выберите помол.');
                }

                if (! $type->requiresGrind() && $this->input('grind') !== null) {
                    $validator->errors()->add('grind', 'У этого товара нет помола.');
                }
            },
        ];
    }
}
