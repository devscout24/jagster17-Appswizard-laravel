@extends('layouts.admin')

@section('title', 'Contractor Services')
@section('page_title', 'Contractor Service Offerings')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Services</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.services.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search service name, description, contractor..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select name="pricing_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Pricing Types</option>
                    <option value="fixed" {{ $pricingType === 'fixed' ? 'selected' : '' }}>Fixed</option>
                    <option value="hourly" {{ $pricingType === 'hourly' ? 'selected' : '' }}>Hourly</option>
                    <option value="sqft" {{ $pricingType === 'sqft' ? 'selected' : '' }}>Sqft</option>
                    <option value="custom_quote" {{ $pricingType === 'custom_quote' ? 'selected' : '' }}>Custom Quote</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-1 text-end">
                <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-light w-100" title="Reset Filters">
                    <i class="ti ti-refresh"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Service Name</th>
                        <th>Contractor</th>
                        <th>Category</th>
                        <th>Pricing Model</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td>
                                <strong class="d-block text-dark">{{ $service->name }}</strong>
                                <small class="text-muted">{{ Str::limit($service->description, 50) }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.contractors.show', $service->business_id) }}" class="fw-semibold text-primary">
                                    {{ $service->business?->businessProfile?->business_name ?? $service->business?->name }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $service->category?->name ?? 'General' }}</span>
                            </td>
                            <td>
                                @if($service->pricing_type === 'custom_quote')
                                    <span class="badge bg-secondary-subtle text-secondary">Custom Quote</span>
                                @else
                                    <span class="fw-bold text-success">${{ number_format($service->price, 2) }}</span>
                                    <small class="text-muted">/ {{ $service->unit ?? $service->pricing_type }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $service->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                    {{ $service->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.services.show', $service->id) }}" class="btn btn-sm btn-icon btn-light" title="View Service">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit Service">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Service">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No contractor services found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($services->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $services->links() }}
        </div>
    @endif
</div>
@endsection

