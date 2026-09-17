@extends('layouts.admin')

@section('title', 'Subscription Billing History')
@section('page_title', 'Contractor Subscription Billing Transactions')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
    <li class="breadcrumb-item active">Billing History</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Subscriptions
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <h5 class="card-title mb-0 fw-semibold">Subscription Invoices &amp; Receipts Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Contractor Business</th>
                        <th>Plan Billed</th>
                        <th>Amount</th>
                        <th>Billed Date</th>
                        <th>Status</th>
                        <th class="text-end">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $item)
                        <tr>
                            <td>
                                <strong class="text-dark d-block">
                                    {{ $item->subscription?->business?->businessProfile?->business_name ?? $item->subscription?->business?->name }}
                                </strong>
                                <small class="text-muted">{{ $item->subscription?->business?->email }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary fs-xs text-uppercase">{{ $item->plan_name }}</span>
                            </td>
                            <td>
                                <strong class="text-success">${{ number_format($item->amount, 2) }}</strong>
                            </td>
                            <td class="fs-xs text-muted">
                                {{ $item->billed_at?->format('M d, Y') }}
                            </td>
                            <td>
                                <span class="badge {{ $item->status === 'paid' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($item->receipt_url)
                                    <a href="{{ $item->receipt_url }}" target="_blank" class="btn btn-sm btn-icon btn-light" title="View External Receipt">
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                @else
                                    <span class="text-muted fs-xs">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No subscription billing history recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($history->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $history->links() }}
        </div>
    @endif
</div>
@endsection

