@extends('layouts.admin')

@section('title', 'Customer Management')
@section('page_title', 'Homeowners & Clients')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Customers</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.customers.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search customer name, email, phone, city..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Customer / Client</th>
                        <th>Location</th>
                        <th>Phone</th>
                        <th>Activity (Quotes / Projects)</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset($customer->avatar ?? 'assets/images/users/user-5.jpg') }}" class="avatar-sm rounded-circle me-2" alt="Avatar" />
                                    <div>
                                        <a href="{{ route('admin.customers.show', $customer->id) }}" class="fw-semibold text-primary d-block">
                                            {{ $customer->name }}
                                        </a>
                                        <small class="text-muted">{{ $customer->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span><i class="ti ti-map-pin fs-xs text-muted me-1"></i>{{ $customer->customerProfile?->city ?? 'N/A' }}, {{ $customer->customerProfile?->state ?? '' }}</span>
                            </td>
                            <td>
                                <span>{{ $customer->phone ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">{{ $customer->customer_quote_requests_count }} Quotes</span>
                                <span class="badge bg-success-subtle text-success">{{ $customer->customer_projects_count }} Projects</span>
                            </td>
                            <td>
                                <span class="badge {{ $customer->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                    {{ $customer->status ?? 'active' }}
                                </span>
                            </td>
                            <td class="fs-xs text-muted">
                                {{ $customer->created_at?->format('M d, Y') }}
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.customers.show', $customer->id) }}">
                                                <i class="ti ti-eye me-2 text-muted"></i> View Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.customers.edit', $customer->id) }}">
                                                <i class="ti ti-edit me-2 text-muted"></i> Edit Account
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ti ti-trash me-2"></i> Delete Customer
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No customer records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($customers->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection

