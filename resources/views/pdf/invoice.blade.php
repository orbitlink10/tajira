<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    <style>
        @page { margin: 32px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0b1f3a; font-size: 11px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; }
        .muted { color: #527497; }
        .header { width: 100%; border-bottom: 2px solid #0b1f3a; padding-bottom: 14px; }
        .header td { vertical-align: top; }
        .brand { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        .box { background: #f5f7fa; border: 1px solid #cfdae8; padding: 10px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.items th { background: #0b1f3a; color: #fff; text-align: left; padding: 7px; font-size: 10px; }
        table.items td { border-bottom: 1px solid #e9eef5; padding: 7px; vertical-align: top; }
        .right { text-align: right; }
        .totals { width: 46%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        .totals td { padding: 5px 0; }
        .totals .total { border-top: 2px solid #0b1f3a; font-size: 13px; font-weight: bold; }
        .status { display: inline-block; padding: 3px 8px; border: 1px solid #cfdae8; border-radius: 3px; font-size: 10px; }
        .footer { margin-top: 30px; border-top: 1px solid #cfdae8; padding-top: 10px; font-size: 9px; color: #527497; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ $business['name'] }}</div>
                @if($business['legal_name'])<div class="muted">{{ $business['legal_name'] }}</div>@endif
                @if($business['address'])<div class="muted">{{ $business['address'] }}</div>@endif
                <div class="muted">{{ collect([$business['city'], $business['postal_code']])->filter()->implode(' ') }}</div>
                @if($business['phone'])<div class="muted">{{ $business['phone'] }}</div>@endif
                @if($business['email'])<div class="muted">{{ $business['email'] }}</div>@endif
                @if($business['tax_pin'])<div class="muted">Tax PIN: {{ $business['tax_pin'] }}</div>@endif
            </td>
            <td class="right">
                <h1>{{ $order->isPaid() ? 'Invoice' : 'Proforma invoice' }}</h1>
                <div><strong>{{ $order->number }}</strong></div>
                <div class="muted">{{ $order->placed_at?->format('j F Y') }}</div>
                <div class="status">{{ $order->isPaid() ? 'PAID' : strtoupper(str_replace('_', ' ', $order->payment_status)) }}</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; margin-top:16px;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div class="muted" style="font-size:9px; text-transform:uppercase;">Billed to</div>
                <div><strong>{{ $order->customer_name }}</strong></div>
                @if($order->company)<div>{{ $order->company }}</div>@endif
                <div>{{ $order->customer_email }}</div>
                <div>{{ $order->customer_phone }}</div>
                <div>{{ $order->address_line }}</div>
                <div>{{ $order->town }}, {{ $order->county }}</div>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div class="muted" style="font-size:9px; text-transform:uppercase;">Delivery</div>
                <div>{{ $order->shipping_method_name ?: 'To be confirmed' }}</div>
                @if($order->shipping_lead_time)<div>{{ $order->shipping_lead_time }}</div>@endif
                <div>{{ $order->delivery_type === 'pickup' ? 'Pickup' : 'Delivery' }}</div>
                @if($order->payment_method)<div>Payment method: {{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th style="width:44%;">Item</th><th>SKU</th><th class="right">Unit</th><th class="right">Qty</th><th class="right">Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->sku }}</td>
                    <td class="right">{{ money($item->unit_price_minor) }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ money($item->line_total_minor) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="right">{{ money($order->subtotal_minor) }}</td></tr>
        @if($order->discount_minor > 0)
            <tr><td class="muted">Discount</td><td class="right">− {{ money($order->discount_minor) }}</td></tr>
        @endif
        <tr><td class="muted">{{ $order->tax_inclusive ? 'Tax included' : 'Tax' }} ({{ $order->tax_rate_bp / 100 }}%)</td><td class="right">{{ money($order->tax_minor) }}</td></tr>
        <tr><td class="muted">Delivery</td><td class="right">{{ $order->shipping_minor === 0 ? 'Free' : money($order->shipping_minor) }}</td></tr>
        <tr class="total"><td>Total</td><td class="right">{{ $order->formattedTotal() }}</td></tr>
    </table>

    <div class="footer">
        <p>All amounts are in {{ $order->currency }}. Tax treatment is configured by {{ $business['name'] }} and may require confirmation. This document is not an eTIMS receipt, and no eTIMS compliance is claimed.</p>
        @if(!$order->isPaid())
            <p>This proforma invoice is not proof of payment. Payment must be verified on Tajira's server before the order is treated as paid.</p>
        @endif
        @if($business['domain'])<p>{{ $business['domain'] }}</p>@endif
    </div>
</body>
</html>
