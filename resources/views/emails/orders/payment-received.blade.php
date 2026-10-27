<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Payment confirmed</title></head>
<body style="margin:0; padding:0; background:#f5f7fa; font-family:Arial, sans-serif; color:#0b1f3a;">
    <div style="max-width:600px; margin:0 auto; padding:24px;">
        <div style="background:#0b1f3a; color:#fff; padding:20px; border-radius:8px 8px 0 0;">
            <div style="font-size:20px; font-weight:bold; letter-spacing:1px;">{{ setting('business.name', 'Tajira') }}</div>
            <div style="font-size:13px; color:#b0eeea; margin-top:4px;">Payment confirmed</div>
        </div>
        <div style="background:#fff; padding:24px; border:1px solid #cfdae8; border-top:0;">
            <p style="margin-top:0;">Hello {{ $order->customer_name }},</p>
            <p>We have confirmed payment for order <strong>{{ $order->number }}</strong>. The order total is <strong>{{ $order->formattedTotal() }}</strong>.</p>
            <p>Our team will now proceed with processing and delivery. We will contact you if anything else is needed.</p>
            <p><a href="{{ route('orders.show', $order->number) }}" style="display:inline-block; background:#126a6e; color:#fff; padding:10px 16px; border-radius:6px; text-decoration:none;">View order</a></p>
            <p style="font-size:12px; color:#527497;">Keep this email for your records. You can download the invoice from your order page.</p>
        </div>
        <div style="padding:16px; font-size:11px; color:#527497; text-align:center;">
            {{ setting('business.name', 'Tajira') }} · {{ setting('business.city', 'Nairobi') }}, Kenya
        </div>
    </div>
</body>
</html>
