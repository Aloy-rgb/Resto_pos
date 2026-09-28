<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $orders = Order::with(['items.menuItem.category', 'diningTable'])
            ->whereNotIn('status', ['paid', 'cancelled'])->latest()->get();
        $menuItems = MenuItem::with('category')->where('is_available', true)->orderBy('name')->get();
        $categories = Category::with(['menuItems' => fn ($query) => $query->where('is_available', true)])->orderBy('name')->get();
        $period = $request->validate(['period' => ['sometimes', 'in:daily,monthly,annually']])['period'] ?? 'daily';
        $start = match ($period) {
            'monthly' => now()->startOfMonth(),
            'annually' => now()->startOfYear(),
            default => now()->startOfDay(),
        };
        $paid = Order::with(['payments', 'diningTable'])->where('payment_status', 'paid')
            ->where('paid_at', '>=', $start)->where('paid_at', '<=', now())->latest('paid_at')->get();
        $methods = ['cash', 'card', 'gcash'];
        $paymentMix = [];
        foreach ($methods as $method) {
            $amount = (float) $paid->sum(fn ($order) => $order->payments->where('method', $method)->sum('amount'));
            $paymentMix[$method] = ['amount' => $amount, 'percent' => $paid->sum('total') > 0 ? (int) round($amount / $paid->sum('total') * 100) : 0];
        }
        $salesSummary = [
            'period' => $period,
            'total' => (float) $paid->sum('total'),
            'orders' => $paid->count(),
            'cash' => $paymentMix['cash']['amount'],
            'card' => $paymentMix['card']['amount'],
            'gcash' => $paymentMix['gcash']['amount'],
            'pending' => (float) Order::where('payment_status', 'pending')->whereNotIn('status', ['cancelled'])->sum('total'),
            'paymentMix' => $paymentMix,
            'statusCounts' => Order::whereDate('created_at', today())->select('status', DB::raw('count(*) as aggregate'))->groupBy('status')->pluck('aggregate', 'status'),
            'recentSales' => $paid->take(10),
        ];

        return view('pos.index', compact('orders', 'menuItems', 'categories', 'salesSummary', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedOrder($request);
        DB::transaction(function () use ($request, $data): void {
            $table = DiningTable::firstOrCreate(['table_number' => $data['table_number']], ['capacity' => 4]);
            $order = Order::create([
                'dining_table_id' => $table->id,
                'user_id' => $request->user()->id,
                'customer_name' => $data['customer_name'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'total' => 0,
            ]);
            $this->replaceItems($order, $data['items']);
            $table->update(['status' => 'occupied']);
        });

        return back()->with('success', 'Order sent to the kitchen.');
    }

    public function edit(Order $order): View
    {
        abort_unless($order->payment_status === 'pending' && $order->status !== 'cancelled', 404);
        return view('pos.edit', ['order' => $order->load('items'), 'menuItems' => MenuItem::with('category')->where('is_available', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->payment_status === 'pending' && ! in_array($order->status, ['cancelled', 'paid'], true), 422);
        $data = $this->validatedOrder($request);
        DB::transaction(function () use ($request, $data, $order): void {
            $table = DiningTable::firstOrCreate(['table_number' => $data['table_number']], ['capacity' => 4]);
            $oldTableId = $order->dining_table_id;
            $order->update(['dining_table_id' => $table->id, 'customer_name' => $data['customer_name'], 'status' => 'pending']);
            $this->replaceItems($order, $data['items']);
            $table->update(['status' => 'occupied']);
            $this->releaseTableIfEmpty($oldTableId);
        });
        return redirect()->route('pos.index')->with('success', 'Order updated.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        abort_unless($order->payment_status === 'pending' && ! in_array($order->status, ['paid', 'cancelled'], true), 422);
        DB::transaction(function () use ($order): void {
            $order->update(['status' => 'cancelled']);
            $this->releaseTableIfEmpty($order->dining_table_id);
        });
        return back()->with('success', 'Order cancelled.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:cooking,ready,served']]);
        $allowed = match ($order->status) {
            'pending' => ['cooking'], 'cooking' => ['ready'], 'ready' => ['served'], default => [],
        };
        abort_unless(in_array($data['status'], $allowed, true), 422, 'That order status change is not allowed.');
        abort_unless($request->user()->role === 'admin' || ($request->user()->role === 'chef' && in_array($data['status'], ['cooking', 'ready'], true)) || ($request->user()->role === 'cashier' && $data['status'] === 'served'), 403);
        $order->update(['status' => $data['status']]);
        return back()->with('success', 'Order status updated.');
    }

    public function pay(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['payment_method' => ['required', 'in:cash,card,gcash']]);
        abort_unless($order->status === 'served' && $order->payment_status === 'pending', 422, 'Only served, unpaid orders can be paid.');
        DB::transaction(function () use ($order, $data): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedOrder->status === 'served' && $lockedOrder->payment_status === 'pending', 422);
            $paidAt = now();
            abort_if($lockedOrder->payments()->exists(), 422, 'A payment is already recorded for this order.');
            $lockedOrder->payments()->create(['amount' => $lockedOrder->total, 'method' => $data['payment_method'], 'paid_at' => $paidAt]);
            $lockedOrder->update(['payment_status' => 'paid', 'status' => 'paid', 'invoice_number' => 'INV-'.$lockedOrder->id.'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)), 'paid_at' => $paidAt]);
            $this->releaseTableIfEmpty($lockedOrder->dining_table_id);
        });
        return back()->with('success', 'Payment recorded.');
    }

    public function check(Order $order): View
    {
        return view('pos.check', ['order' => $order->load(['items.menuItem', 'diningTable'])]);
    }

    private function validatedOrder(Request $request): array
    {
        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'table_number' => ['required', 'integer', 'min:1', 'max:999'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'distinct', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
    }

    private function replaceItems(Order $order, array $items): void
    {
        $ids = collect($items)->pluck('menu_item_id');
        $menuItems = MenuItem::whereIn('id', $ids)->where('is_available', true)->get()->keyBy('id');
        abort_unless($menuItems->count() === $ids->unique()->count(), 422, 'One or more selected menu items are unavailable.');
        $total = 0;
        $rows = [];
        foreach ($items as $item) {
            $menuItem = $menuItems->get((int) $item['menu_item_id']);
            $quantity = (int) $item['quantity'];
            $price = (float) $menuItem->current_price;
            $total += $price * $quantity;
            $rows[] = ['menu_item_id' => $menuItem->id, 'quantity' => $quantity, 'unit_price' => $price];
        }
        $order->items()->delete();
        $order->items()->createMany($rows);
        $order->update(['total' => $total]);
    }

    private function releaseTableIfEmpty(int $tableId): void
    {
        $hasOpenOrders = Order::where('dining_table_id', $tableId)->whereNotIn('status', ['paid', 'cancelled'])->exists();
        if (! $hasOpenOrders) DiningTable::whereKey($tableId)->update(['status' => 'available']);
    }
}
