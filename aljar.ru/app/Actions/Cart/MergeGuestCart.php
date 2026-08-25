<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

/**
 * Слить гостевую корзину с корзиной покупателя при входе.
 *
 * Иначе набранное до входа исчезало бы ровно в тот момент, когда человек
 * решил довести покупку до конца.
 */
class MergeGuestCart
{
    public function handle(Login $event): void
    {
        $customer = $event->user;

        if (! $customer instanceof Customer) {
            return;
        }

        $token = request()->cookie((string) config('checkout.cart_cookie'));

        if (! is_string($token) || $token === '') {
            return;
        }

        $guestCart = Cart::query()->whereNull('customer_id')->with('items')->firstWhere('token', $token);

        if ($guestCart === null) {
            return;
        }

        $customerCart = Cart::query()->firstWhere('customer_id', $customer->id);

        // Своей корзины ещё нет — гостевая просто становится его.
        if ($customerCart === null) {
            $guestCart->forceFill(['customer_id' => $customer->id])->save();
            $guestCart->keepAlive();

            return;
        }

        DB::transaction(function () use ($guestCart, $customerCart): void {
            foreach ($guestCart->items as $item) {
                $existing = $customerCart->items()
                    ->where('product_variant_id', $item->product_variant_id)
                    ->where('grind', $item->grind)
                    ->first();

                // Одинаковые позиции складываются, остальные переезжают.
                if ($existing instanceof CartItem) {
                    $existing->increment('qty', $item->qty);

                    continue;
                }

                $customerCart->items()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'grind' => $item->grind,
                    'qty' => $item->qty,
                ]);
            }

            $guestCart->delete();
        });

        $customerCart->keepAlive();

        Cookie::queue($this->cookieName(), $customerCart->token, (int) config('checkout.cart_lifetime_days') * 24 * 60);
    }

    protected function cookieName(): string
    {
        return (string) config('checkout.cart_cookie');
    }
}
