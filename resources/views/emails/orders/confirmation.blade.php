<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Order received</title></head>
<body style="margin:0; padding:0; background:#f5f7fa; font-family:Arial, sans-serif; color:#0b1f3a;">
    <div style="max-width:600px; margin:0 auto; padding:24px;">
        <div style="background:#0b1f3a; color:#fff; padding:20px; border-radius:8px 8px 0 0;">
            <div style="font-size:20px; font-weight:bold; letter-spacing:1px;">{{ setting('business.name', 'Tajira') }}</div>
            <div style="font-size:13px; color:#b0eeea; margin-top:4px;">Order {{ $order->number }}</div>
        </div>
        <div style="background:#fff; padding:24px; border:1px solid #cfdae8; border-top:0;">
            <p style="margin-top:0;">Hello {{ $order->customer_name }},</p>
            <p>We have received your order. It is now <strong>{{ $order->statusLabel() }}</strong> and payment is <strong>{{ $order->paymentStatusLabel() }}</strong>.</p>
            @if(!$order->isPaid())
                <p style="background:#fdf6e7; border:1px solid #f6d78e; padding:12px; border-radius:6px;">
                    Your stock is reserved temporarily. Complete payment to confirm the order. If payment is not confirmed within the reservation window, the order may be cancelled and stock released.
                </p>
            @endif
            <table style="width:100%; border-collapse:collapse; margin:18px 0;">
                @foreach($order->items as $item)
                    <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #e9eef5;">{{ $item->name }} × {{ $item->quantity }}</td>
                        <td style="padding:8px 0; border-bottom:1px solid #e9eef5; text-align:right;">{{ money($item->line_total_minor) }}</td>
                    </tr>
                @endforeach
                <tr><td style="padding:8px 0;"><strong>Total</strong></td><td style="padding:8px 0; text-align:right;"><strong>{{ $order->formattedTotal() }}</strong></td></tr>
            </table>
            <p><a href="{{ route('orders.show', $order->number) }}" style="display:inline-block; background:#126a6e; color:#fff; padding:10px 16px; border-radius:6px; text-decoration:none;">View your order</a></p>
            <p style="font-size:12px; color:#527497;">Delivery: {{ $order->shipping_method_name ?: 'To be confirmed' }}. We will confirm lead times and payment status by email.</p>
        </div>
        <div style="padding:16px; font-size:11px; color:#527497; text-align:center;">
            {{ setting('business.name', 'Tajira') }} · {{ setting('business.city', 'Nairobi') }}, Kenya
            @if(setting('business.email')) · {{ setting('business.email') }}@endif
        </div>
    </div>
</body>
</html>
