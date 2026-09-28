<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Check for Order #{{ $order->id }}</title>
    <style>
        body{font:15px/1.45 Arial,sans-serif;color:#111;max-width:360px;margin:24px auto;padding:16px}h1,p{text-align:center;margin:.25rem 0}.items{border-top:1px dashed #555;border-bottom:1px dashed #555;margin:18px 0;padding:10px 0}.row{display:flex;justify-content:space-between;gap:12px;padding:4px 0}.total{font-weight:bold;font-size:1.15rem}.actions{text-align:center;margin-top:20px}button,a{padding:9px 14px}@media print{.actions{display:none}body{margin:0 auto;padding:0}}
    </style>
</head>
<body>
    <h1>{{ config('app.name', 'Resto POS') }}</h1>
    <p>Guest check</p>
    <p>Order #{{ $order->id }} · Table {{ $order->table_number }}</p>
    <p>{{ $order->customer_name }} · {{ now()->format('M j, Y g:i A') }}</p>
    <div class="items">
        @foreach ($order->items as $item)
            <div class="row"><span>{{ $item->quantity }} × {{ $item->menuItem->name }}</span><span>${{ number_format($item->line_total, 2) }}</span></div>
        @endforeach
    </div>
    <div class="row total"><span>Total due</span><span>${{ number_format($order->total, 2) }}</span></div>
    <p>Payment due when ready</p>
    <div class="actions"><button onclick="window.print()">Print check</button> <a href="{{ route('pos.index') }}">Return to orders</a></div>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
