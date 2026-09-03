<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PayMethod;
use App\Enums\ShipMethod;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\NumberSequence;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Оформить заказ из корзины.
 *
 * Здесь корзина превращается в документ: цены, названия и веса
 * фиксируются снимком, остаток уменьшается, корзина очищается.
 */
class PlaceOrder
{
    /**
     * @param  array{contact_name?: string|null, phone: string, email?: string|null, ship_method: ShipMethod, address?: string|null, pay_method: PayMethod, comment?: string|null}  $data
     */
    public function __invoke(Cart $cart, array $data, ?Customer $customer = null): Order
    {
        $cart->load('items.variant.product');

        if ($cart->items->isEmpty()) {
            throw new RuntimeException('Корзина пуста.');
        }

        return DB::transaction(function () use ($cart, $data, $customer): Order {
            $goodsTotal = $cart->goodsTotal();

            $order = Order::query()->create([
                'number' => NumberSequence::next('order'),
                'customer_id' => $customer?->id,
                'contact_name' => $data['contact_name'] ?? $customer?->name,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? $customer?->email,
                'ship_method' => $data['ship_method'],
                'address' => $data['address'] ?? null,
                'delivery_price' => $data['ship_method']->price($goodsTotal),
                'pay_method' => $data['pay_method'],
                'comment' => $data['comment'] ?? null,
                'status' => OrderStatus::New,
                'placed_at' => now(),
            ]);

            foreach ($cart->items as $item) {
                $this->line($order, $item);
            }

            // Корзина оформлена — дальше это уже заказ.
            $cart->items()->delete();

            return $order;
        });
    }

    protected function line(Order $order, CartItem $item): void
    {
        $variant = $item->variant;

        $order->lines()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_title' => $variant->title,
            'weight_g' => $variant->weight_g,
            // Цена со скидкой подписки, а не каталожная: заказ обязан
            // сойтись с тем, что покупатель видел в корзине.
            'unit_price' => $item->unitPrice(),
            'grind' => $item->grind,
            'roast' => $item->roast,
            'subscribe' => $item->subscribe,
            'qty' => $item->qty,
        ]);

        // Остаток уменьшается сразу: между синхронизациями с учётной
        // системой сайт остаётся единственным, кто знает о продаже.
        $variant->decrement('stock', min($item->qty, $variant->stock));
    }
}
