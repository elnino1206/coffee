<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Корзина. Хранится в базе, а не в сессии: авторизованный покупатель
 * начинает на телефоне и дополняет с компьютера.
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string $token
 * @property Carbon $expires_at
 */
#[Fillable(['customer_id', 'token', 'expires_at'])]
class Cart extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
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
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Стоимость товаров в копейках. Считается по текущим ценам: снимок
     * делается только при оформлении заказа.
     */
    public function goodsTotal(): int
    {
        return $this->items->sum(fn (CartItem $item) => $item->total());
    }

    /**
     * Сколько всего пачек в корзине.
     *
     * Имя не count(): оно перекрыло бы статический Cart::count() —
     * Eloquent пробрасывает такие вызовы в конструктор запросов, и
     * подсчёт корзин в базе падал бы с ошибкой.
     */
    public function itemCount(): int
    {
        return (int) $this->items->sum('qty');
    }

    /**
     * Отодвинуть срок жизни: месяц считается от последнего изменения.
     */
    public function keepAlive(): void
    {
        $this->forceFill([
            'expires_at' => now()->addDays((int) config('checkout.cart_lifetime_days')),
        ])->save();
    }
}
