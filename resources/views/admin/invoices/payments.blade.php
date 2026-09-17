@extends('layouts.admin')

@section('title', 'Customer Payments')
@section('page_title', 'Customer Payment Transactions Log')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">Payments Log</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Invoices
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.payments.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search transaction ID, client, contractor..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Payment Statuses</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid / Successful</option>
                    <option value="refunded" {{ $status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
                    <i class="ti ti-refresh me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaction ID</th>
                        <th>Client</th>
                        <th>Contractor</th>
                        <th>Invoice #</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Paid Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                <code>{{ $payment->transaction_id }}</code>
                            </td>
                            <td>{{ $payment->user?->name ?? 'Customer' }}</td>
                            <td>
                                <a href="{{ route('admin.contractors.show', $payment->business_id) }}" class="fw-medium text-dark">
                                    {{ $payment->business?->businessProfile?->business_name ?? $payment->business?->name }}
                                </a>
                            </td>
                            <td>
                                @if($payment->invoice)
                                    <a href="{{ route('admin.invoices.show', $payment->invoice_id) }}" class="fw-semibold text-primary">
                                        {{ $payment->invoice->invoice_number }}
                                    </a>
                                @else
                                    <span class="text-muted">Direct</span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-success">${{ number_format($payment->amount, 2) }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $payment->payment_method }}</span>
                            </td>
                            <td class="fs-xs text-muted">
                                {{ $payment->paid_at?->format('M d, Y H:i') }}
                            </td>
                            <td>
                                <span class="badge {{ $payment->status === 'paid' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                    {{ $payment->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No payment transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($payments->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection

