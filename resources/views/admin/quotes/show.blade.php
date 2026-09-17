@extends('layouts.admin')

@section('title', 'Quote Request ' . $quote->reference_number)
@section('page_title', 'Quote Request ' . $quote->reference_number)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.quotes.index') }}">Quotes</a></li>
    <li class="breadcrumb-item active">{{ $quote->reference_number }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.quotes.edit', $quote->id) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-edit me-1"></i> Edit / Assign Quote
    </a>
    <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Quotes
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Quote Summary & Details -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">{{ $quote->project_title }}</h5>
                @php
                    $badge = match($quote->status) {
                        'accepted' => 'bg-success-subtle text-success',
                        'quoted' => 'bg-info-subtle text-info',
                        'new', 'pending' => 'bg-warning-subtle text-warning',
                        'declined', 'rejected', 'expired' => 'bg-danger-subtle text-danger',
                        default => 'bg-secondary-subtle text-secondary',
                    };
                @endphp
                <span class="badge {{ $badge }} text-uppercase fs-sm">{{ $quote->status }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Service Category</label>
                        <span class="badge bg-light text-dark border fs-sm">{{ $quote->category?->name ?? 'General Request' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Requested Timeline</label>
                        <strong class="text-dark fs-sm text-capitalize">{{ str_replace('_', ' ', $quote->timeline ?? 'flexible') }}</strong>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Work Location Address</label>
                        <span class="text-dark fs-sm">
                            {{ $quote->street_address ?? 'Address not specified' }}, {{ $quote->city ?? '' }} {{ $quote->state ?? '' }} {{ $quote->zip_code ?? '' }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Customer Estimated Budget</label>
                        <span class="fw-semibold text-dark fs-sm">
                            @if($quote->budget_min || $quote->budget_max)
                                ${{ number_format($quote->budget_min) }} - ${{ number_format($quote->budget_max) }}
                            @else
                                {{ $quote->budget_range ?? 'Flexible' }}
                            @endif
                        </span>
                    </div>
                    <div class="col-md-12 border-top pt-3">
                        <label class="text-muted fs-xs d-block mb-1">Project Scope &amp; Details</label>
                        <p class="text-dark fs-sm mb-0">{{ $quote->description ?? 'No additional description provided.' }}</p>
                    </div>
                </div>

                <!-- Financial Breakdown if Quoted -->
                @if($quote->quote_amount || $quote->labor_cost || $quote->materials_cost)
                    <div class="bg-light p-3 rounded border my-3">
                        <h6 class="fw-semibold mb-3">Contractor Official Quote Estimate</h6>
                        <div class="row g-3 text-center">
                            <div class="col-md-3">
                                <span class="text-muted fs-xs d-block">Labor Cost</span>
                                <strong class="text-dark">${{ number_format($quote->labor_cost ?? 0, 2) }}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted fs-xs d-block">Materials Cost</span>
                                <strong class="text-dark">${{ number_format($quote->materials_cost ?? 0, 2) }}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted fs-xs d-block">Tax / Fees</span>
                                <strong class="text-dark">${{ number_format($quote->tax_cost ?? 0, 2) }}</strong>
                            </div>
                            <div class="col-md-3 border-start">
                                <span class="text-muted fs-xs d-block">Total Quoted</span>
                                <strong class="text-success fs-base">${{ number_format($quote->quote_amount ?? 0, 2) }}</strong>
                            </div>
                        </div>
                        @if($quote->estimated_duration)
                            <div class="mt-3 text-muted fs-xs">
                                <strong>Estimated Duration:</strong> {{ $quote->estimated_duration }}
                            </div>
                        @endif
                        @if($quote->contractor_notes)
                            <div class="mt-2 text-muted fs-xs">
                                <strong>Contractor Notes:</strong> {{ $quote->contractor_notes }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Parties (Customer & Assigned Contractor) -->
    <div class="col-xl-4">
        <!-- Customer Info -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Customer Details</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <img src="{{ asset($quote->customer?->avatar ?? 'assets/images/users/user-5.jpg') }}" class="avatar-md rounded-circle me-3" alt="Avatar" />
                    <div>
                        <a href="{{ $quote->customer ? route('admin.customers.show', $quote->customer->id) : '#' }}" class="fw-semibold text-primary d-block">
                            {{ $quote->customer?->name ?? 'Homeowner Client' }}
                        </a>
                        <small class="text-muted">{{ $quote->customer?->email ?? 'N/A' }}</small>
                    </div>
                </div>
                <ul class="list-unstyled mb-0 fs-sm">
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Phone:</span>
                        <span>{{ $quote->customer?->phone ?? 'N/A' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Location:</span>
                        <span>{{ $quote->city ?? 'N/A' }}, {{ $quote->state ?? '' }}</span>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted">Submitted Date:</span>
                        <span>{{ $quote->requested_at?->format('M d, Y H:i') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Assigned Contractor Info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Assigned Contractor</h6>
            </div>
            <div class="card-body">
                @if($quote->business)
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ asset($quote->business->avatar ?? 'assets/images/users/user-1.jpg') }}" class="avatar-md rounded-circle me-3" alt="Avatar" />
                        <div>
                            <a href="{{ route('admin.contractors.show', $quote->business->id) }}" class="fw-semibold text-primary d-block">
                                {{ $quote->business->businessProfile?->business_name ?? $quote->business->name }}
                            </a>
                            <small class="text-muted">Owner: {{ $quote->business->name }}</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-0 fs-sm">
                        <li class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">Phone:</span>
                            <span>{{ $quote->business->phone ?? 'N/A' }}</span>
                        </li>
                        <li class="mb-2 d-flex justify-content-between">
                            <span class="text-muted">License:</span>
                            <span>{{ $quote->business->businessProfile?->license_number ?? 'N/A' }}</span>
                        </li>
                    </ul>
                @else
                    <div class="text-center py-3">
                        <i class="ti ti-user-x fs-28 text-muted d-block mb-1"></i>
                        <p class="text-muted fs-sm mb-2">No contractor assigned to this lead yet.</p>
                        <a href="{{ route('admin.quotes.edit', $quote->id) }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-user-plus me-1"></i> Assign Contractor
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

