<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Quotation request received</title></head>
<body style="margin:0; padding:0; background:#f5f7fa; font-family:Arial, sans-serif; color:#0b1f3a;">
    <div style="max-width:600px; margin:0 auto; padding:24px;">
        <div style="background:#0b1f3a; color:#fff; padding:20px; border-radius:8px 8px 0 0;">
            <div style="font-size:20px; font-weight:bold; letter-spacing:1px;">{{ setting('business.name', 'Tajira') }}</div>
            <div style="font-size:13px; color:#b0eeea; margin-top:4px;">Quotation request {{ $quotation->number }}</div>
        </div>
        <div style="background:#fff; padding:24px; border:1px solid #cfdae8; border-top:0;">
            <p style="margin-top:0;">Hello {{ $quotation->name }},</p>
            <p>We have received your quotation request. Our team will review the items and respond with confirmed pricing, availability, warranty and delivery terms.</p>

            @if($quotation->items->isNotEmpty())
                <table style="width:100%; border-collapse:collapse; margin:16px 0;">
                    <thead><tr><th style="text-align:left; padding:6px; background:#f5f7fa;">Item</th><th style="text-align:right; padding:6px; background:#f5f7fa;">Qty</th></tr></thead>
                    <tbody>
                        @foreach($quotation->items as $item)
                            <tr><td style="padding:6px; border-bottom:1px solid #e9eef5;">{{ $item->name }}</td><td style="padding:6px; border-bottom:1px solid #e9eef5; text-align:right;">{{ $item->quantity }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if($quotation->message)
                <p style="font-size:12px; color:#527497;">Your notes: {{ $quotation->message }}</p>
            @endif

            <p style="background:#fdf6e7; border:1px solid #f6d78e; padding:12px; border-radius:6px; font-size:13px;">This email acknowledges a request. It is not a final quotation or a binding offer.</p>
            <p><a href="{{ route('quotes.show', $quotation->number) }}" style="display:inline-block; background:#126a6e; color:#fff; padding:10px 16px; border-radius:6px; text-decoration:none;">View your request</a></p>
        </div>
        <div style="padding:16px; font-size:11px; color:#527497; text-align:center;">
            {{ setting('business.name', 'Tajira') }}
            @if(setting('business.email')) · {{ setting('business.email') }}@endif
        </div>
    </div>
</body>
</html>
