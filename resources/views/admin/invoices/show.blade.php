@extends('layouts.admin')

@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page_title', 'Invoice ' . $invoice->invoice_number)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
@endsection

@section('page_actions')
    <button onclick="window.print()" class="btn btn-sm btn-outline-dark">
        <i class="ti ti-printer me-1"></i> Print Invoice
    </button>
    <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-edit me-1"></i> Edit Invoice
    </a>
    <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Invoices
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm p-4">
            <!-- Invoice Header -->
            <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
                <div>
                    <img src="{{ asset('assets/images/logo-black.png') }}" alt="ValorHub" height="32" class="mb-2" />
                    <p class="text-muted fs-xs mb-0">Contractor Milestone Billing System</p>
                </div>
                <div class="text-end">
                    <h3 class="fw-bold text-primary mb-1">INVOICE</h3>
                    <h5 class="fw-semibold text-dark mb-1">#{{ $invoice->invoice_number }}</h5>
                    @php
                        $invBadge = match($invoice->status) {
                            'paid' => 'bg-success-subtle text-success',
                            'pending' => 'bg-warning-subtle text-warning',
                            'overdue' => 'bg-danger-subtle text-danger',
                            'draft' => 'bg-secondary-subtle text-secondary',
                            default => 'bg-secondary-subtle text-secondary',
                        };
                    @endphp
                    <span class="badge {{ $invBadge }} text-uppercase fs-xs">{{ $invoice->status }}</span>
                </div>
            </div>

            <!-- Billed From & Billed To -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <h6 class="text-muted text-uppercase fs-xs fw-bold mb-2">Billed By (Contractor):</h6>
                    <h5 class="fw-bold text-dark mb-1">{{ $invoice->business?->businessProfile?->business_name ?? $invoice->business?->name }}</h5>
                    <p class="text-muted fs-sm mb-1">Owner: {{ $invoice->business?->name }}</p>
                    <p class="text-muted fs-sm mb-1">Phone: {{ $invoice->business?->phone ?? 'N/A' }}</p>
                    <p class="text-muted fs-sm mb-0">{{ $invoice->business?->businessProfile?->city }}, {{ $invoice->business?->businessProfile?->state }} {{ $invoice->business?->businessProfile?->zip_code }}</p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <h6 class="text-muted text-uppercase fs-xs fw-bold mb-2">Billed To (Client):</h6>
                    <h5 class="fw-bold text-dark mb-1">{{ $invoice->customer?->name ?? 'Customer' }}</h5>
                    <p class="text-muted fs-sm mb-1">Email: {{ $invoice->customer?->email }}</p>
                    <p class="text-muted fs-sm mb-1">Phone: {{ $invoice->customer?->phone ?? 'N/A' }}</p>
                    <p class="text-muted fs-sm mb-0">{{ $invoice->customer?->customerProfile?->city }}, {{ $invoice->customer?->customerProfile?->state }}</p>
                </div>
            </div>

            <!-- Dates Row -->
            <div class="row g-2 bg-light p-3 rounded mb-4 text-center fs-sm">
                <div class="col-4">
                    <span class="text-muted d-block fs-xs">Invoice Date:</span>
                    <strong>{{ $invoice->issued_at?->format('M d, Y') }}</strong>
                </div>
                <div class="col-4">
                    <span class="text-muted d-block fs-xs">Due Date:</span>
                    <strong class="text-danger">{{ $invoice->due_at?->format('M d, Y') }}</strong>
                </div>
                <div class="col-4">
                    <span class="text-muted d-block fs-xs">Payment Date:</span>
                    <strong class="text-success">{{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('M d, Y') : 'Unpaid' }}</strong>
                </div>
            </div>

            <!-- Invoice Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-end" style="width: 140px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($invoice->labor_amount)
                            <tr>
                                <td>
                                    <strong class="d-block">Contractor Labor &amp; Professional Services</strong>
                                    <small class="text-muted">Direct project labor milestone billing</small>
                                </td>
                                <td class="text-end fw-semibold">${{ number_format($invoice->labor_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if($invoice->materials_amount)
                            <tr>
                                <td>
                                    <strong class="d-block">Materials, Parts &amp; Equipment</strong>
                                    <small class="text-muted">Procured building materials and supplies</small>
                                </td>
                                <td class="text-end fw-semibold">${{ number_format($invoice->materials_amount, 2) }}</td>
                            </tr>
                        @endif
                        @if(!$invoice->labor_amount && !$invoice->materials_amount)
                            <tr>
                                <td>
                                    <strong class="d-block">Project Milestone Work</strong>
                                    <small class="text-muted">{{ $invoice->project?->title ?? 'Contractor Milestone' }}</small>
                                </td>
                                <td class="text-end fw-semibold">${{ number_format($invoice->amount, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="text-end fw-bold">Subtotal:</td>
                            <td class="text-end fw-bold">${{ number_format($invoice->amount, 2) }}</td>
                        </tr>
                        @if($invoice->platform_fee)
                            <tr>
                                <td class="text-end text-muted fs-sm">Platform Fee (included):</td>
                                <td class="text-end text-muted fs-sm">${{ number_format($invoice->platform_fee, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="table-light">
                            <td class="text-end fw-bold fs-base text-primary">Total Balance Due:</td>
                            <td class="text-end fw-bold fs-base text-primary">${{ number_format($invoice->amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($customerPayment)
                <div class="bg-success-subtle p-3 rounded border border-success-subtle mb-4">
                    <h6 class="fw-bold text-success mb-1"><i class="ti ti-circle-check me-1"></i> Paid via {{ $customerPayment->payment_method }}</h6>
                    <small class="text-muted d-block">Transaction Reference: <code>{{ $customerPayment->transaction_id }}</code> on {{ $customerPayment->paid_at?->format('M d, Y H:i') }}</small>
                </div>
            @endif

            @if($invoice->notes)
                <div class="border-top pt-3 text-muted fs-sm">
                    <strong>Invoice Notes / Terms:</strong>
                    <p class="mb-0">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

