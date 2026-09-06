<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\ShipMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Заказы в админке.
 *
 * Состав и сумма не редактируются: сумма считается по позициям, а чужой
 * выбор помола нельзя менять молча — это правило пришло из макета
 * админки и остаётся в силе. Правятся статус, контакты, способ доставки
 * и комментарий для склада.
 */
class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);

        $orders = Order::query()
            ->with('lines')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $needle = '%'.mb_strtolower($term).'%';

                $query->where(fn (Builder $where) => $where
                    ->whereRaw('lower(number) like ?', [$needle])
                    ->orWhereRaw('lower(contact_name) like ?', [$needle])
                    ->orWhereRaw('lower(phone) like ?', [$needle]));
            })
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Orders', [
            'orders' => $orders->through(fn (Order $order) => $this->row($order)),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'ships' => collect(ShipMethod::cases())->map(fn (ShipMethod $ship) => [
                'value' => $ship->value,
                'label' => $ship->label(),
            ]),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'ship_method' => ['required', Rule::enum(ShipMethod::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Заказ {$order->number} обновлён."]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Order $order): array
    {
        return [
            'number' => $order->number,
            'placed_at' => $order->placedAtLabel(),
            'contact_name' => $order->contact_name,
            'phone' => $order->phone,
            'email' => $order->email,
            'ship_method' => $order->ship_method->value,
            'ship_label' => $order->ship_method->label(),
            'address' => $order->address,
            'comment' => $order->comment,
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
