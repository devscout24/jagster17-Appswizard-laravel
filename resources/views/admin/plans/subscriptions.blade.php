@extends('layouts.admin')

@section('title', 'Active Contractor Subscriptions')
@section('page_title', 'Active Contractor Subscriptions')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
    <li class="breadcrumb-item active">Subscriptions</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.billing-history.index') }}" class="btn btn-sm btn-outline-primary me-2">
        <i class="ti ti-history me-1"></i> Billing History
    </a>
    <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-light">
        <i class="ti ti-crown me-1"></i> Manage Membership Plans
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.subscriptions.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Subscription Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="past_due" {{ $status === 'past_due' ? 'selected' : '' }}>Past Due</option>
                </select>
            </div>
            <div class="col-md-9 text-end">
                <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Contractor Business</th>
                        <th>Plan Name</th>
                        <th>Billing Cycle</th>
                        <th>Renewal Date</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $sub)
                        <tr>
                            <td>
                                <a href="{{ route('admin.contractors.show', $sub->business_id) }}" class="fw-semibold text-primary">
                                    {{ $sub->business?->businessProfile?->business_name ?? $sub->business?->name }}
                                </a>
                                <small class="text-muted d-block">{{ $sub->business?->email }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary fs-xs text-uppercase">{{ $sub->plan?->name ?? 'Custom Plan' }}</span>
                            </td>
                            <td>
                                <span class="text-capitalize">{{ $sub->billing_cycle }}</span>
                                <small class="text-muted">(${{ number_format($sub->billing_cycle === 'annual' ? ($sub->plan?->annual_price ?? 0) : ($sub->plan?->monthly_price ?? 0), 2) }})</small>
                            </td>
                            <td>
                                <span>{{ $sub->renews_at?->format('M d, Y') }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">•••• {{ $sub->payment_method_last4 ?? 'Card' }}</span>
                            </td>
                            <td>
                                @php
                                    $sBadge = match($sub->status) {
                                        'active' => 'bg-success-subtle text-success',
                                        'cancelled' => 'bg-danger-subtle text-danger',
                                        'past_due' => 'bg-warning-subtle text-warning',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $sBadge }} text-uppercase">{{ $sub->status }}</span>
                            </td>
                            <td class="text-end">
                                @if($sub->status === 'active')
                                    <form action="{{ route('admin.subscriptions.cancel', $sub->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Cancel Plan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted fs-xs">No actions</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No active subscriptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($subscriptions->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $subscriptions->links() }}
        </div>
    @endif
</div>
@endsection

