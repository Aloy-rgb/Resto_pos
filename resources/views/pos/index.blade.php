<x-app-layout>
    <div class="pos-shell">
        <header class="pos-header">
            <div>
                <p class="eyebrow">{{ config('app.name', 'Resto POS') }}</p>
                <h1>{{ auth()->user()->role === 'manager' ? ucfirst($salesSummary['period']).' Sales' : (auth()->user()->role === 'chef' ? 'Kitchen Board' : 'Service Board') }}</h1>
                <p class="muted">{{ auth()->user()->role === 'manager' ? 'Review paid sales and service activity.' : 'Track every table through its current service step.' }}</p>
            </div>
            <div class="user-chip">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(auth()->user()->role) }}</small></span>
            </div>
        </header>

        @if (session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash-error">{{ $errors->first() }}</div>
        @endif

        @if (in_array(auth()->user()->role, ['cashier', 'admin'], true))
        <div class="pos-grid">
            <section class="panel order-panel">
                <div class="panel-heading">
                    <div><p class="eyebrow">New ticket</p><h2>Start an order</h2></div>
                    <span class="status-dot paid">Ready</span>
                </div>
                <form method="POST" action="{{ route('pos.orders.store') }}" id="order-form">
                    @csrf
                    <div class="field-row">
                        <label>Customer name<input name="customer_name" required placeholder="e.g. Maria Santos"></label>
                        <label>Table<input name="table_number" required type="number" min="1" placeholder="12"></label>
                    </div>
                    <div class="menu-list">
                        @forelse ($menuItems as $item)
                            <label class="menu-item">
                                <input type="checkbox" name="items[{{ $item->id }}][menu_item_id]" value="{{ $item->id }}" data-price="{{ $item->current_price }}">
                                <span class="menu-copy"><strong>{{ $item->name }}</strong><small>{{ $item->category->name }} · {{ $item->description }}</small></span>
                                <span class="menu-price">${{ number_format($item->current_price, 2) }}</span>
                                <input class="quantity" type="number" name="items[{{ $item->id }}][quantity]" min="1" max="99" value="1" disabled aria-label="{{ $item->name }} quantity">
                            </label>
                        @empty
                            <p class="muted">No menu items yet. Run the database seeder to load the sample menu.</p>
                        @endforelse
                    </div>
                    <div class="form-footer"><span class="muted">Selected items: <strong id="selected-count">0</strong></span><button class="button button-primary" type="submit">Send to kitchen <span>→</span></button></div>
                </form>
            </section>

            <section class="panel live-panel">
                <div class="panel-heading live-heading">
                    <div><p class="eyebrow">02 / Live rail</p><h2>Orders in service</h2></div>
                    <div class="order-tools">
                        <label class="search-field" for="order-search"><span aria-hidden="true">⌕</span><input id="order-search" type="search" placeholder="Find order, table, or guest" autocomplete="off"><button type="button" id="clear-order-search" aria-label="Clear order search" hidden>×</button></label>
                        <span class="order-count" id="live-order-count">{{ $orders->count() }} {{ $orders->count() === 1 ? 'ticket' : 'tickets' }}</span>
                    </div>
                </div>
                <div class="orders-list">
                    @forelse ($orders as $order)
                        <article class="ticket {{ $order->payment_status === 'paid' ? 'ticket-paid' : '' }} {{ $order->status === 'cancelled' ? 'ticket-cancelled' : '' }}">
                            <div class="ticket-top">
                                <div class="ticket-identity"><span class="ticket-number">Order #{{ $order->id }}</span><strong>Table {{ $order->table_number }} <small class="table-state {{ $order->status === 'paid' || $order->status === 'cancelled' ? 'available' : 'occupied' }}">{{ $order->status === 'paid' || $order->status === 'cancelled' ? 'Available' : 'Occupied' }}</small></strong><small>{{ $order->customer_name }}</small></div>
                                <span class="status-dot {{ $order->payment_status === 'paid' ? 'paid' : ($order->status === 'cancelled' ? 'cancelled' : ($order->payment_status === 'pending' ? 'pending' : '')) }}" role="status">{{ $order->payment_status === 'paid' ? 'Paid' : $order->status_label }}</span>
                            </div>
                            <div class="ticket-items" aria-label="Ordered items">@foreach ($order->items as $item)<span><b>{{ $item->quantity }} ×</b> {{ $item->menuItem->name }}</span>@endforeach</div>
                            <div class="ticket-bottom"><strong>${{ number_format($order->total, 2) }}</strong><span>{{ $order->invoice_number ?? 'Invoice pending' }}</span></div>
                            <div class="ticket-actions">
                                @if (in_array(auth()->user()->role, ['cashier', 'admin'], true) && !in_array($order->status, ['paid', 'cancelled'], true))
                                    <div class="ticket-secondary-actions">
                                        <a class="button button-small" href="{{ route('pos.orders.edit', $order) }}" aria-label="Change order number {{ $order->id }}">Change order</a>
                                        <form method="POST" action="{{ route('pos.orders.cancel', $order) }}" onsubmit="return confirm('Cancel order #{{ $order->id }}?')">@csrf<button class="button button-cancel" type="submit" aria-label="Cancel order number {{ $order->id }}">Cancel order</button></form>
                                    </div>
                                @endif
                                @if (auth()->user()->role === 'chef' && $order->status === 'pending')
                                    <form class="primary-ticket-action" method="POST" action="{{ route('pos.orders.status', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cooking"><button class="button button-small" type="submit">Start cooking <span aria-hidden="true">→</span></button></form>
                                @elseif (auth()->user()->role === 'chef' && $order->status === 'cooking')
                                    <form class="primary-ticket-action" method="POST" action="{{ route('pos.orders.status', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="ready"><button class="button button-small" type="submit">Mark ready <span aria-hidden="true">→</span></button></form>
                                @elseif (in_array(auth()->user()->role, ['cashier', 'admin'], true) && $order->status === 'ready')
                                    <form class="primary-ticket-action" method="POST" action="{{ route('pos.orders.status', $order) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="served"><button class="button button-small" type="submit">Serve table <span aria-hidden="true">→</span></button></form>
                                @elseif (in_array(auth()->user()->role, ['cashier', 'admin'], true) && $order->status === 'served')
                                    <form method="POST" action="{{ route('pos.orders.pay', $order) }}" class="pay-form">@csrf<select name="payment_method" aria-label="Payment method"><option value="cash">Cash</option><option value="card">Card</option><option value="gcash">GCash</option></select><button class="button button-pay" type="submit">Take payment</button></form>
                                @elseif ($order->status === 'cancelled')
                                    <span class="cancelled-note">Cancelled</span>
                                @else
                                    <span class="paid-note">Paid {{ $order->paid_at?->format('H:i') }}</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="empty-state"><span>◎</span><strong>No tickets on the rail</strong><p>Start with a customer and table on the left.</p></div>
                    @endforelse
                    <div class="empty-state order-search-empty" id="order-search-empty" hidden><span>⌕</span><strong>No matching orders</strong><p>Try an order number, table, guest, item, or status.</p></div>
                </div>
            </section>
        </div>
        @endif

        @if (auth()->user()->role === 'manager')
            <section class="panel manager-panel">
                <div class="panel-heading">
                    <div><p class="eyebrow">Manager control room</p><h2>{{ ucfirst($salesSummary['period']) }} sales oversight</h2></div>
                    <form method="GET" action="{{ route('pos.index') }}"><label>Sales period <select name="period" onchange="this.form.submit()"><option value="daily" @selected($salesSummary['period'] === 'daily')>Daily</option><option value="monthly" @selected($salesSummary['period'] === 'monthly')>Monthly</option><option value="annually" @selected($salesSummary['period'] === 'annually')>Annually</option></select></label></form>
                </div>
                <div class="sales-metrics">
                    <div class="sales-metric sales-metric-primary"><span>Paid sales {{ $salesSummary['period'] }}</span><strong>${{ number_format($salesSummary['total'], 2) }}</strong><small>{{ $salesSummary['orders'] }} completed {{ $salesSummary['orders'] === 1 ? 'order' : 'orders' }}</small></div>
                    <div class="sales-metric"><span>Cash</span><strong>${{ number_format($salesSummary['cash'], 2) }}</strong><small>Reconcile drawer</small></div>
                    <div class="sales-metric"><span>Card</span><strong>${{ number_format($salesSummary['card'], 2) }}</strong><small>Match terminal</small></div>
                    <div class="sales-metric"><span>GCash</span><strong>${{ number_format($salesSummary['gcash'], 2) }}</strong><small>Match wallet reports</small></div>
                    <div class="sales-metric"><span>Open exposure</span><strong>${{ number_format($salesSummary['pending'], 2) }}</strong><small>Pending active tickets</small></div>
                </div>
                <div class="manager-visuals">
                    <div class="chart-card"><div class="chart-heading"><div><p class="eyebrow">Payment mix</p><h3>Where sales came from</h3></div><span class="chart-total">${{ number_format($salesSummary['total'], 2) }}</span></div><div class="chart-layout"><div class="payment-pie" style="--cash: {{ $salesSummary['paymentMix']['cash']['percent'] }}%; --card: {{ $salesSummary['paymentMix']['card']['percent'] }}%;" role="img" aria-label="Payment mix: cash {{ $salesSummary['paymentMix']['cash']['percent'] }} percent, card {{ $salesSummary['paymentMix']['card']['percent'] }} percent, GCash {{ $salesSummary['paymentMix']['gcash']['percent'] }} percent"></div><div class="chart-legend"><span><i class="legend-cash"></i>Cash <b>{{ $salesSummary['paymentMix']['cash']['percent'] }}%</b></span><span><i class="legend-card"></i>Card <b>{{ $salesSummary['paymentMix']['card']['percent'] }}%</b></span><span><i class="legend-mobile"></i>GCash <b>{{ $salesSummary['paymentMix']['gcash']['percent'] }}%</b></span></div></div></div>
                    <div class="chart-card"><div class="chart-heading"><div><p class="eyebrow">Order flow</p><h3>Today's ticket status</h3></div></div><div class="status-bars">@foreach (['paid' => 'Paid', 'served' => 'Awaiting payment', 'cooking' => 'Cooking', 'ready' => 'Ready', 'pending' => 'New', 'cancelled' => 'Cancelled'] as $status => $label)<div class="status-bar-row"><span>{{ $label }}</span><div class="status-bar"><i style="width: {{ min(100, (($salesSummary['statusCounts'][$status] ?? 0) / max(1, $salesSummary['statusCounts']->sum())) * 100) }}%"></i></div><b>{{ $salesSummary['statusCounts'][$status] ?? 0 }}</b></div>@endforeach</div></div>
                </div>
                <div class="manager-table-wrap"><div class="chart-heading"><div><p class="eyebrow">Reconciliation log</p><h3>Latest paid transactions</h3></div><span class="order-count">{{ $salesSummary['orders'] }} in period</span></div><table class="sales-table"><thead><tr><th>Invoice</th><th>Table</th><th>Paid at</th><th>Method</th><th class="amount-cell">Amount</th></tr></thead><tbody>@forelse ($salesSummary['recentSales'] as $sale)<tr><td>{{ $sale->invoice_number }}</td><td>{{ $sale->table_number }}</td><td>{{ $sale->paid_at?->format('H:i') }}</td><td><span class="method-badge">{{ ucfirst($sale->payments->first()?->method ?? '') }}</span></td><td class="amount-cell">${{ number_format($sale->total, 2) }}</td></tr>@empty<tr><td colspan="5" class="table-empty">No paid transactions in this period.</td></tr>@endforelse</tbody></table></div>
                <div class="manager-checks">
                    <div><p class="eyebrow">Daily controls</p><ul class="control-list"><li><span>01</span> Reconcile cash, card, and digital receipts</li><li><span>02</span> Compare sales against daily targets</li><li><span>03</span> Verify stock levels and pricing compliance</li></ul></div>
                    <div><p class="eyebrow">Team and risk</p><ul class="control-list"><li><span>04</span> Set targets and run the team huddle</li><li><span>05</span> Align staffing with peak service hours</li><li><span>06</span> Record safe drops and maintain audit logs</li></ul></div>
                </div>
            </section>
        @endif

    </div>
    <script>
        document.querySelectorAll('.menu-item input[type="checkbox"]').forEach((checkbox) => checkbox.addEventListener('change', () => {
            checkbox.closest('.menu-item').querySelector('.quantity').disabled = !checkbox.checked;
            document.querySelector('#selected-count').textContent = document.querySelectorAll('.menu-item input[type="checkbox"]:checked').length;
        }));

        const orderSearch = document.querySelector('#order-search');
        const clearOrderSearch = document.querySelector('#clear-order-search');
        const orderSearchEmpty = document.querySelector('#order-search-empty');
        const orderCount = document.querySelector('#live-order-count');
        const tickets = [...document.querySelectorAll('.ticket')];
        const totalTickets = tickets.length;

        orderSearch?.addEventListener('input', () => {
            const query = orderSearch.value.trim().toLowerCase();
            let visibleTickets = 0;

            tickets.forEach((ticket) => {
                const matches = ticket.textContent.toLowerCase().includes(query);
                ticket.hidden = !matches;
                if (matches) visibleTickets += 1;
            });

            clearOrderSearch.hidden = query.length === 0;
            orderSearchEmpty.hidden = query.length === 0 || visibleTickets > 0;
            orderCount.textContent = query.length === 0
                ? `${totalTickets} ${totalTickets === 1 ? 'ticket' : 'tickets'}`
                : `${visibleTickets} of ${totalTickets} shown`;
        });

        clearOrderSearch?.addEventListener('click', () => {
            orderSearch.value = '';
            orderSearch.dispatchEvent(new Event('input'));
            orderSearch.focus();
        });
    </script>
</x-app-layout>
