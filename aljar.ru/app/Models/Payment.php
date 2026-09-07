<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Попытка оплаты заказа.
 *
 * Заказ считается оплаченным только по уведомлению провайдера, а не по
 * возвращению покупателя на страницу успеха: её адрес можно открыть
 * руками. Поэтому оплата — запись со своим состоянием, а не флаг на
 * заказе.
 *
 * @property int $id
 * @property int $order_id
 * @property string $provider
 * @property string|null $provider_payment_id
 * @property int $amount
 * @property PaymentStatus $status
 * @property Carbon|null $paid_at
 * @property bool $binds_card
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'order_id', 'provider', 'provider_payment_id', 'amount', 'status',
    'paid_at', 'binds_card', 'payload',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'binds_card' => 'boolean',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
