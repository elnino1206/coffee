<?php

namespace App\Models;

use App\Concerns\FormatsRussianDates;
use App\Enums\Grind;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Регулярная отгрузка кофе.
 *
 * Самостоятельная сущность, а не состояние заказа: пауза и отмена — её
 * статусы, а удерживающее предложение при отмене некуда прицепить, если
 * запись исчезает.
 *
 * @property int $id
 * @property string $number
 * @property int $customer_id
 * @property int|null $product_variant_id
 * @property Grind|null $grind
 * @property int $frequency_weeks
 * @property Carbon|null $next_delivery_at
 * @property SubscriptionStatus $status
 * @property int $discount_percent
 * @property Carbon|null $paused_at
 * @property Carbon|null $blocked_at
 * @property Carbon|null $canceled_at
 * @property string|null $cancel_reason
 * @property int $failed_charge_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number', 'customer_id', 'product_variant_id', 'grind', 'frequency_weeks',
    'next_delivery_at', 'status', 'discount_percent', 'paused_at', 'blocked_at',
    'canceled_at', 'cancel_reason', 'failed_charge_count',
])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use FormatsRussianDates, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grind' => Grind::class,
            'status' => SubscriptionStatus::class,
            'next_delivery_at' => 'date',
            'paused_at' => 'datetime',
            'blocked_at' => 'datetime',
            'canceled_at' => 'datetime',
            'frequency_weeks' => 'integer',
            'discount_percent' => 'integer',
            'failed_charge_count' => 'integer',
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
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Limit the query to subscriptions that are still alive.
     *
     * Активная и поставленная на паузу — обе ещё чьи-то: у отменённой и
     * заблокированной отгрузок не будет.
     *
     * @param  Builder<Subscription>  $query
     */
    public function scopeRunning(Builder $query): void
    {
        $query->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused]);
    }

    /**
     * Списание за одну отгрузку в копейках.
     *
     * Скидка берётся из самой подписки, а не из настроек: процент
     * зафиксирован в момент оформления, и правка настройки не должна
     * менять условия тем, кто подписался раньше.
     *
     * Вариант мог уйти из продажи — тогда суммы нет, и показывать вместо
     * неё ноль нельзя: это разные вещи.
     */
    public function chargeTotal(): ?int
    {
        if ($this->variant === null) {
            return null;
        }

        return (int) round($this->variant->price * (1 - $this->discount_percent / 100));
    }

    /**
     * Дата следующей отгрузки для интерфейса.
     *
     * Показывается только у активной подписки: у поставленной на паузу,
     * заблокированной и отменённой отгрузки нет, и дата в строке
     * читалась бы как обещание её привезти.
     */
    public function nextDeliveryLabel(): ?string
    {
        if ($this->status !== SubscriptionStatus::Active || $this->next_delivery_at === null) {
            return null;
        }

        return $this->russianDate($this->next_delivery_at);
    }

    /**
     * Можно ли покупателю распоряжаться подпиской.
     *
     * Отменённая — история, её не трогают. Заблокированную снимает с
     * блокировки удачная оплата, а не кнопка в кабинете.
     */
    public function isManageable(): bool
    {
        return in_array($this->status, [SubscriptionStatus::Active, SubscriptionStatus::Paused], true);
    }

    /**
     * Можно ли ещё отменить.
     *
     * Заблокированную отменить можно: покупатель вправе прекратить
     * попытки списания, не дожидаясь, пока карта заработает.
     */
    public function isCancelable(): bool
    {
        return $this->status !== SubscriptionStatus::Canceled;
    }

    /**
     * Поставить на паузу.
     *
     * Дата следующей отгрузки снимается: пока подписка стоит, везти
     * нечего, и сохранённая дата читалась бы как обещание.
     */
    public function pause(): void
    {
        $this->update([
            'status' => SubscriptionStatus::Paused,
            'paused_at' => now(),
            'next_delivery_at' => null,
        ]);
    }

    /**
     * Снять с паузы.
     *
     * Цикл отсчитывается заново от сегодня, а не продолжается с
     * отложенной даты: она давно прошла, и подписка отгрузилась бы
     * немедленно.
     */
    public function resume(): void
    {
        $this->update([
            'status' => SubscriptionStatus::Active,
            'paused_at' => null,
            'next_delivery_at' => now()->addWeeks($this->frequency_weeks)->toDateString(),
        ]);
    }

    /**
     * Отменить.
     *
     * Запись остаётся: по ТЗ 5.2 при отмене показывается удерживающее
     * предложение, а историю подписки покупателю ещё видно в кабинете.
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'cancel_reason' => $reason,
            'next_delivery_at' => null,
        ]);
    }
}
