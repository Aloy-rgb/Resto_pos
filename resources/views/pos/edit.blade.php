<x-app-layout>
    <div class="pos-shell">
        <header class="pos-header">
            <div>
                <p class="eyebrow">Order #{{ $order->id }} / Change ticket</p>
                <h1>Change order</h1>
                <p class="muted">Update the table or items, then send the ticket back to the kitchen.</p>
            </div>
            <a class="button button-small" href="{{ route('pos.index') }}">← Back to service</a>
        </header>

        @if ($errors->any())
            <div class="flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="panel order-panel">
            <form method="POST" action="{{ route('pos.orders.update', $order) }}" id="change-order-form">
                @csrf
                @method('PUT')
                <div class="field-row">
                    <label>Customer name<input name="customer_name" required value="{{ old('customer_name', $order->customer_name) }}"></label>
                    <label>Table<input name="table_number" required type="number" min="1" max="999" value="{{ old('table_number', $order->table_number) }}"></label>
                </div>
                <div class="menu-list">
                    @foreach ($menuItems as $item)
                        @php($existingItem = $order->items->firstWhere('menu_item_id', $item->id))
                        <label class="menu-item">
                            <input type="checkbox" name="items[{{ $item->id }}][menu_item_id]" value="{{ $item->id }}" {{ $existingItem ? 'checked' : '' }}>
                            <span class="menu-copy"><strong>{{ $item->name }}</strong><small>{{ $item->category->name }} · {{ $item->description }}</small></span>
                            <span class="menu-price">${{ number_format($item->current_price, 2) }}</span>
                            <input class="quantity" type="number" name="items[{{ $item->id }}][quantity]" min="1" max="99" value="{{ $existingItem?->quantity ?? 1 }}" {{ $existingItem ? '' : 'disabled' }} aria-label="{{ $item->name }} quantity">
                        </label>
                    @endforeach
                </div>
                <div class="form-footer"><span class="muted">Changes reset the ticket to <strong>New order</strong>.</span><button class="button button-primary" type="submit">Save changes <span>→</span></button></div>
            </form>
        </section>
    </div>
    <script>
        document.querySelectorAll('.menu-item input[type="checkbox"]').forEach((checkbox) => checkbox.addEventListener('change', () => {
            checkbox.closest('.menu-item').querySelector('.quantity').disabled = !checkbox.checked;
        }));
    </script>
</x-app-layout>
