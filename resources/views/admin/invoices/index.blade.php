@extends('layouts.admin')

@section('title', 'Invoices & Billing')
@section('page_title', 'Billing & Digital Invoices')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Invoices</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-outline-primary">
        <i class="ti ti-credit-card me-1"></i> Customer Payments Log
    </a>
@endsection

@section('content')
<!-- Financial Highlights Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <span class="text-muted fs-xs fw-semibold text-uppercase d-block mb-1">Paid Invoices</span>
                <h4 class="fw-bold text-success mb-0">${{ number_format($totalPaid, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <span class="text-muted fs-xs fw-semibold text-uppercase d-block mb-1">Pending Payment</span>
                <h4 class="fw-bold text-warning mb-0">${{ number_format($totalPending, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <span class="text-muted fs-xs fw-semibold text-uppercase d-block mb-1">Overdue Volume</span>
                <h4 class="fw-bold text-danger mb-0">${{ number_format($totalOverdue, 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <span class="text-muted fs-xs fw-semibold text-uppercase d-block mb-1">Platform Fees Earned</span>
                <h4 class="fw-bold text-primary mb-0">${{ number_format($totalPlatformFees, 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.invoices.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search invoice number, client, contractor..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Invoice Statuses</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Invoice #</th>
                        <th>Contractor</th>
                        <th>Client</th>
                        <th>Amount &amp; Fee</th>
                        <th>Issued / Due</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('admin.invoices.show', $inv->id) }}" class="fw-bold text-primary">
                                    {{ $inv->invoice_number }}
                                </a>
                                @if($inv->project)
                                    <div class="fs-xs text-muted">{{ Str::limit($inv->project->title, 25) }}</div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.contractors.show', $inv->business_id) }}" class="fw-medium text-dark">
                                    {{ $inv->business?->businessProfile?->business_name ?? $inv->business?->name }}
                                </a>
                            </td>
                            <td>
                                <div>{{ $inv->customer?->name ?? 'Customer' }}</div>
                                <small class="text-muted">{{ $inv->customer?->email }}</small>
                            </td>
                            <td>
                                <strong class="text-dark d-block">${{ number_format($inv->amount, 2) }}</strong>
                                @if($inv->platform_fee)
                                    <small class="text-success">Fee: ${{ number_format($inv->platform_fee, 2) }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="fs-xs text-muted">
                                    <span>Issued: {{ $inv->issued_at?->format('M d, Y') }}</span>
                                    <span class="d-block">Due: {{ $inv->due_at?->format('M d, Y') }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $invBadge = match($inv->status) {
                                        'paid' => 'bg-success-subtle text-success',
                                        'pending' => 'bg-warning-subtle text-warning',
                                        'overdue' => 'bg-danger-subtle text-danger',
                                        'draft' => 'bg-secondary-subtle text-secondary',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $invBadge }} text-uppercase">{{ $inv->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.invoices.show', $inv->id) }}" class="btn btn-sm btn-icon btn-light" title="View Invoice">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('admin.invoices.edit', $inv->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit Invoice">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('admin.invoices.destroy', $inv->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this invoice?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Invoice">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($invoices->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $invoices->links() }}
        </div>
    @endif
</div>
@endsection

