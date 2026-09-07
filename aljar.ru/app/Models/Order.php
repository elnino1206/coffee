<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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
 * @property int|null $subscription_id
 * @property string|null $contact_name
 * @property string $phone
 * @property string|null $email
 * @property ShipMethod $ship_method
 * @property int|null $city_code
 * @property string|null $city
 * @property string|null $delivery_point_code
 * @property string|null $address
 * @property int $delivery_price
 * @property PayMethod $pay_method
 * @property string|null $comment
 * @property OrderStatus $status
 * @property Carbon $placed_at
 */
#[Fillable([
    'number', 'customer_id', 'subscription_id', 'contact_name', 'phone', 'email',
    'ship_method', 'city_code', 'city', 'delivery_point_code', 'address',
    'delivery_price', 'pay_method', 'comment', 'status', 'placed_at',
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
            'city_code' => 'integer',
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
     * Подписка, по которой пришла эта отгрузка.
     *
     * У разовой покупки её нет. Ссылка нужна кабинету: без неё история
     * заказов покажет только разовые, и повтор в один клик не увидит
     * регулярные.
     *
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Попытки оплаты. Их может быть несколько: отказ банка не отменяет
     * заказ, и покупатель платит ещё раз.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Отгрузки. Обычно одна; вторая появляется, когда невручённый заказ
     * отправляют заново.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
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
     * Оплачен ли заказ.
     *
     * Считается по удачному платежу, а не по возвращению покупателя на
     * страницу успеха: её адрес можно открыть руками.
     */
    public function isPaid(): bool
    {
        return $this->payments->contains(
            fn (Payment $payment) => $payment->status === PaymentStatus::Succeeded,
        );
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
