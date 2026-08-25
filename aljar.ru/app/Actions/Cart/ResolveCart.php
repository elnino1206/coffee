<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Найти корзину запроса, при необходимости заведя новую.
 *
 * У авторизованного покупателя корзина привязана к нему, у гостя — к
 * токену в куке. Кука долгоживущая: месяц, как и сама корзина.
 */
class ResolveCart
{
    public function __invoke(Request $request, bool $create = true): ?Cart
    {
        $customer = $request->user();

        if ($customer instanceof Customer) {
            $cart = Cart::query()->firstWhere('customer_id', $customer->id);

            if ($cart === null && $create) {
                $cart = $this->create($customer->id);
            }

            return $cart;
        }

        $token = $request->cookie($this->cookieName());
        $cart = is_string($token) && $token !== ''
            ? Cart::query()->whereNull('customer_id')->firstWhere('token', $token)
            : null;

        if ($cart === null && $create) {
            $cart = $this->create();
        }

        return $cart;
    }

    protected function create(?int $customerId = null): Cart
    {
        $lifetime = (int) config('checkout.cart_lifetime_days');

        $cart = Cart::query()->create([
            'customer_id' => $customerId,
            'token' => (string) Str::uuid(),
            'expires_at' => now()->addDays($lifetime),
        ]);

        // Кука ставится всегда, даже покупателю: он может выйти из
        // аккаунта, и корзина не должна пропадать вместе с сессией.
        Cookie::queue($this->cookieName(), $cart->token, $lifetime * 24 * 60);

        return $cart;
    }

    protected function cookieName(): string
    {
        return (string) config('checkout.cart_cookie');
    }
}
