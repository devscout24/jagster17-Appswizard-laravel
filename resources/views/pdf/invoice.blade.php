<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; margin: 0; }
        .wrap { padding: 32px 40px; }
        table { width: 100%; border-collapse: collapse; }
        .brand { font-size: 22px; font-weight: bold; letter-spacing: 1px; color: #111827; }
        .brand span { color: #b91c1c; }
        .muted { color: #6b7280; }
        .title { font-size: 26px; font-weight: bold; text-align: right; color: #111827; }
        .meta td { padding: 2px 0; }
        .meta td.label { color: #6b7280; text-align: right; padding-right: 8px; }
        .section { margin-top: 28px; }
        .items th { background: #f3f4f6; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; color: #4b5563; }
        .items td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .right, .items th.right { text-align: right; }
        .totals td { padding: 6px 8px; }
        .totals .due td { font-weight: bold; font-size: 14px; border-top: 2px solid #111827; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 10px; background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .footer { margin-top: 36px; color: #6b7280; }
    </style>
</head>
<body>
<div class="wrap">
    <table>
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="brand">VALOR<span>HUB</span></div>
                <div style="margin-top: 10px; font-weight: bold;">{{ $profile?->business_name ?? $business->name }}</div>
                @if ($businessAddress)<div class="muted">{{ $businessAddress }}</div>@endif
                <div class="muted">{{ collect([$business->email, $profile?->phone_number ?? $business->phone])->filter()->join(' · ') }}</div>
            </td>
            <td style="vertical-align: top;">
                <div class="title">INVOICE</div>
                <table class="meta" style="margin-top: 8px;">
                    <tr><td class="label">Invoice Number:</td><td class="right">{{ $invoice->invoice_number }}</td></tr>
                    <tr><td class="label">Invoice Date:</td><td class="right">{{ $invoice->issued_at?->format('M j, Y') }}</td></tr>
                    <tr><td class="label">Payment Due:</td><td class="right">{{ $invoice->due_at?->format('M j, Y') }}</td></tr>
                    <tr><td class="label">Status:</td><td class="right"><span class="status">{{ $invoice->status }}</span></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="muted" style="text-transform: uppercase; font-size: 10px;">Bill To</div>
        <div style="font-weight: bold; margin-top: 4px;">{{ $invoice->customer?->name }}</div>
        @if ($invoice->bill_to_address)<div class="muted">{{ $invoice->bill_to_address }}</div>@endif
        <div class="muted">{{ $invoice->customer?->email }}</div>
    </div>

    <table class="items section">
        <thead>
        <tr>
            <th>Description</th>
            <th class="right" style="width: 50px;">Qty</th>
            <th class="right" style="width: 90px;">Price</th>
            <th class="right" style="width: 100px;">Amount</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($items as $item)
            <tr>
                <td>
                    <div style="font-weight: bold;">{{ $item['name'] }}</div>
                    @if (! empty($item['description']))<div class="muted">{{ $item['description'] }}</div>@endif
                </td>
                <td class="right">{{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }}</td>
                <td class="right">${{ number_format($item['price'], 2) }}</td>
                <td class="right">${{ number_format($item['amount'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals" style="width: 45%; margin-left: 55%; margin-top: 12px;">
        <tr><td>Subtotal</td><td class="right">${{ number_format($subtotal, 2) }}</td></tr>
        @if ($invoice->platform_fee)
            <tr><td>Platform fee</td><td class="right">${{ number_format((float) $invoice->platform_fee, 2) }}</td></tr>
        @endif
        <tr class="due"><td>Amount Due (USD)</td><td class="right">${{ number_format($invoice->status === 'paid' ? 0 : (float) $invoice->amount, 2) }}</td></tr>
    </table>

    <div class="footer">
        {{ $invoice->notes ?: 'Thank you for your business.' }}
    </div>
</div>
</body>
</html>
