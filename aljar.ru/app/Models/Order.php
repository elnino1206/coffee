<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PayMethod;
use App\Enums\ShipMethod;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Заказ.
 *
 * Сумма и количество позиций нигде не хранятся отдельным полем — они
 * выводятся из состава. Иначе при первой же правке разойдутся.
 *
 * @property int $id
 * @property string $number
 * @property int|null $customer_id
 * @property string|null $contact_name
 * @property string $phone
 * @property string|null $email
 * @property ShipMethod $ship_method
 * @property string|null $address
 * @property int $delivery_price
 * @property PayMethod $pay_method
 * @property string|null $comment
 * @property OrderStatus $status
 * @property Carbon $placed_at
 */
#[Fillable([
    'number', 'customer_id', 'contact_name', 'phone', 'email', 'ship_method',
    'address', 'delivery_price', 'pay_method', 'comment', 'status', 'placed_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ship_method' => ShipMethod::class,
            'pay_method' => PayMethod::class,
            'status' => OrderStatus::class,
            'delivery_price' => 'integer',
            'placed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Дата заказа для кабинета: «1 сентября 2026».
     *
     * Месяцы заданы списком, а не берутся из локали: приложение живёт в
     * английской локали (русских языковых файлов у Laravel нет, и их
     * включение сломало бы сообщения проверок), а покупателю нужна
     * русская дата.
     */
    public function placedAtLabel(): string
    {
        $months = [
            'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
            'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
        ];

        return $this->placed_at->day.' '.$months[$this->placed_at->month - 1].' '.$this->placed_at->year;
    }

    /**
     * Стоимость товаров в копейках.
     */
    public function goodsTotal(): int
    {
        return $this->lines->sum(fn (OrderLine $line) => $line->total());
    }

    /**
     * К оплате: товары плюс доставка.
     */
    public function total(): int
    {
        return $this->goodsTotal() + $this->delivery_price;
    }
}
