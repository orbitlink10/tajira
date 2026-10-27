<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quotation request {{ $quotation->number }}</title>
    <style>
        @page { margin: 32px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0b1f3a; font-size: 11px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; }
        .muted { color: #527497; }
        .brand { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
        table.head { width: 100%; border-bottom: 2px solid #0b1f3a; padding-bottom: 12px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.items th { background: #0b1f3a; color: #fff; text-align: left; padding: 7px; font-size: 10px; }
        table.items td { border-bottom: 1px solid #e9eef5; padding: 7px; }
        .note { background: #fdf6e7; border: 1px solid #f6d78e; padding: 10px; margin-top: 16px; }
        .footer { margin-top: 30px; border-top: 1px solid #cfdae8; padding-top: 10px; font-size: 9px; color: #527497; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="brand">{{ $business['name'] }}</div>
                @if($business['address'])<div class="muted">{{ $business['address'] }}</div>@endif
                <div class="muted">{{ collect([$business['city']])->filter()->implode(' ') }}</div>
                @if($business['phone'])<div class="muted">{{ $business['phone'] }}</div>@endif
                @if($business['email'])<div class="muted">{{ $business['email'] }}</div>@endif
            </td>
            <td style="text-align:right;">
                <h1>Quotation request</h1>
                <div><strong>{{ $quotation->number }}</strong></div>
                <div class="muted">{{ $quotation->created_at->format('j F Y') }}</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; margin-top:16px;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div class="muted" style="font-size:9px; text-transform:uppercase;">Requested by</div>
                <div><strong>{{ $quotation->name }}</strong></div>
                @if($quotation->company)<div>{{ $quotation->company }}</div>@endif
                <div>{{ $quotation->email }}</div>
                <div>{{ $quotation->phone }}</div>
                <div>{{ collect([$quotation->town, $quotation->county])->filter()->implode(', ') }}</div>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div class="muted" style="font-size:9px; text-transform:uppercase;">Status</div>
                <div>{{ $quotation->statusLabel() }}</div>
                @if($quotation->valid_until)<div>Valid until: {{ $quotation->valid_until->format('j F Y') }}</div>@endif
            </td>
        </tr>
    </table>

    @if($quotation->items->isNotEmpty())
        <table class="items">
            <thead><tr><th style="width:55%;">Requested item</th><th>SKU</th><th>Qty</th><th class="right">Indicative unit</th></tr></thead>
            <tbody>
                @foreach($quotation->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->sku ?: '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td class="right">{{ $item->unit_price_minor !== null ? money($item->unit_price_minor) : 'TBC' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($quotation->message)
        <div style="margin-top:16px;">
            <div class="muted" style="font-size:9px; text-transform:uppercase;">Notes</div>
            <div>{!! nl2br(e($quotation->message)) !!}</div>
        </div>
    @endif

    <div class="note">
        This is a record of a quotation request. It is not a final quotation or an order. Pricing, availability, warranty and delivery terms are confirmed separately by {{ $business['name'] }}.
    </div>

    @if($quotation->total_minor !== null)
        <p class="muted" style="margin-top:12px;">Indicative catalogue value: <strong>{{ $quotation->formattedTotal() }}</strong>. This is not a binding offer.</p>
    @endif

    <div class="footer">{{ $business['domain'] }} · Quotation request {{ $quotation->number }}</div>
</body>
</html>
