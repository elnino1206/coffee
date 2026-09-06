<?php

namespace App\Http\Middleware;

use App\Actions\Cart\ResolveCart;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'admin' => $request->user('admin'),
            ],
            // Счётчики в меню админки: сколько записей ждут разбора.
            // Считаются только внутри админки — витрине они не нужны, а
            // лишний запрос на каждой странице ни к чему.
            'adminCounts' => fn (): array => $request->user('admin') === null
                ? []
                : ['orders' => Order::query()->where('status', OrderStatus::New)->count()],
            // Счётчик в шапке: сумма количеств, а не число строк —
            // две пачки одного сорта это две пачки.
            'cart' => fn (): array => [
                'count' => (int) (app(ResolveCart::class)($request, create: false)?->items()->sum('qty') ?? 0),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
