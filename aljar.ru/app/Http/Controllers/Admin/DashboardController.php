<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\WholesaleLead;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Дашборд админки.
 *
 * Показатели считаются из тех же заказов, что показаны в таблице ниже.
 * Держать их отдельными числами нельзя: после первой же правки статуса
 * или состава они разойдутся с тем, что видно на экране.
 */
class DashboardController extends Controller
{
    public function index(): Response
    {
        $orders = Order::query()->with('lines')->get();

        $newLeads = WholesaleLead::query()->where('status', LeadStatus::New)->count();

        $revenueWeek = $orders
            ->where('status', '!=', OrderStatus::Canceled)
            ->filter(fn (Order $order) => $order->placed_at->greaterThanOrEqualTo(now()->subWeek()))
            ->sum(fn (Order $order) => $order->total());

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                [
                    'label' => 'Заказов сегодня',
                    'value' => (string) $orders->filter(fn (Order $o) => $o->placed_at->isToday())->count(),
                    'note' => 'новых на сайте',
                ],
                [
                    'label' => 'Выручка за неделю',
                    'value' => number_format($revenueWeek / 100, 0, ',', ' ').' ₽',
                    'note' => 'без отменённых',
                ],
                [
                    'label' => 'Ждут обработки',
                    'value' => (string) $orders->where('status', OrderStatus::New)->count(),
                    'note' => 'заказы в статусе «новый»',
                ],
                [
                    'label' => 'Новых заявок опта',
                    'value' => (string) $newLeads,
                    'note' => $newLeads > 0 ? 'ждут ответа' : 'все разобраны',
                ],
            ],
            'orders' => $orders
                ->sortByDesc('placed_at')
                ->take(8)
                ->map(fn (Order $order) => $this->row($order))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Order $order): array
    {
        return [
            'number' => $order->number,
            'placed_at' => $order->placedAtLabel(),
            'customer' => $order->contact_name ?? '—',
            'positions' => (int) $order->lines->sum('qty'),
            'ship' => $order->ship_method->label(),
            'total' => $order->total(),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'lines' => $order->lines->map(fn (OrderLine $line) => [
                'name' => $line->product_name,
                'variant' => $line->variant_title,
                'grind' => $line->grind?->label(),
                'qty' => $line->qty,
            ])->all(),
        ];
    }
}
