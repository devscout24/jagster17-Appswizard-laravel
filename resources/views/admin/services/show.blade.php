@extends('layouts.admin')

@section('title', 'Service Details - ' . $service->name)
@section('page_title', $service->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Services</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-edit me-1"></i> Edit Service
    </a>
    <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">Service Overview</h5>
                <span class="badge {{ $service->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} text-uppercase">
                    {{ $service->status }}
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Contractor Provider</label>
                        <a href="{{ route('admin.contractors.show', $service->business_id) }}" class="fw-semibold fs-base text-primary">
                            {{ $service->business?->businessProfile?->business_name ?? $service->business?->name }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Category</label>
                        <span class="badge bg-light text-dark border fs-sm">{{ $service->category?->name ?? 'Uncategorized' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Pricing Model</label>
                        <strong class="text-dark fs-base text-capitalize">{{ str_replace('_', ' ', $service->pricing_type) }}</strong>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Price / Unit</label>
                        <strong class="text-success fs-base">
                            @if($service->pricing_type === 'custom_quote')
                                Custom Quote
                            @else
                                ${{ number_format($service->price, 2) }} <small class="text-muted">/ {{ $service->unit ?? $service->pricing_type }}</small>
                            @endif
                        </strong>
                    </div>
                    <div class="col-md-12 border-top pt-3">
                        <label class="text-muted fs-xs d-block mb-1">Service Description</label>
                        <p class="text-dark fs-sm mb-0">{{ $service->description ?? 'No description provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

