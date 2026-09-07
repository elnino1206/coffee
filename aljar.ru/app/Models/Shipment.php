<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Посылка, переданная перевозчику.
 *
 * Не поля на заказе: у отгрузки свой номер накладной, своя цепочка
 * состояний и свой ответ перевозчика, а невручённый заказ отправляют
 * повторно — второй отгрузкой того же заказа.
 *
 * @property int $id
 * @property int $order_id
 * @property string $provider
 * @property string|null $provider_uuid
 * @property string|null $track_number
 * @property int|null $tariff_code
 * @property ShipmentStatus $status
 * @property Carbon|null $status_at
 * @property int|null $cost
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'order_id', 'provider', 'provider_uuid', 'track_number', 'tariff_code',
    'status', 'status_at', 'cost', 'payload',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'tariff_code' => 'integer',
            'status_at' => 'datetime',
            'cost' => 'integer',
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

    /**
     * Принять состояние, присланное перевозчиком.
     *
     * Незнакомый код не двигает состояние, но сохраняется: разбирать
     * такие случаи потом придётся по сырому ответу.
     */
    public function applyProviderStatus(string $code, ?Carbon $at = null): void
    {
        $status = ShipmentStatus::fromProvider($code);

        if ($status === null) {
            return;
        }

        $this->update([
            'status' => $status,
            'status_at' => $at ?? now(),
        ]);
    }
}
