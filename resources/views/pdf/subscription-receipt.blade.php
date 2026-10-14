<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        .wrap { padding: 32px 40px; }
        .brand { font-size: 22px; font-weight: bold; letter-spacing: 1px; }
        .brand span { color: #b91c1c; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .total td { font-weight: bold; border-top: 2px solid #111827; border-bottom: none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">VALOR<span>HUB</span></div>
    <h2 style="margin-top: 20px;">Membership Receipt</h2>
    <div class="muted">Receipt #{{ str_pad((string) $bill->id, 6, '0', STR_PAD_LEFT) }} · {{ $bill->billed_at?->format('M j, Y') }}</div>
    <div style="margin-top: 16px;">
        <strong>{{ $profile?->business_name ?? $user->name }}</strong><br>
        <span class="muted">{{ $user->email }}</span>
    </div>
    <table>
        <tr><td>{{ $bill->plan_name }} Membership ({{ $bill->subscription?->billing_cycle ?? 'monthly' }})</td><td class="right">${{ number_format((float) $bill->amount, 2) }}</td></tr>
        <tr class="total"><td>Total ({{ strtoupper($bill->status) }})</td><td class="right">${{ number_format((float) $bill->amount, 2) }}</td></tr>
    </table>
    @if ($bill->subscription?->payment_method_last4)
        <p class="muted">Charged to card ending {{ $bill->subscription->payment_method_last4 }}.</p>
    @endif
</div>
</body>
</html>
