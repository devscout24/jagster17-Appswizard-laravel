@extends('layouts.admin')

@section('title', 'Edit Customer - ' . $customer->name)
@section('page_title', 'Edit Customer Profile')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.show', $customer->id) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom">
        <h5 class="card-title mb-0 fw-semibold">Customer Account Details</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $customer->customerProfile?->city) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="{{ old('state', $customer->customerProfile?->state) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $customer->status) === 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ti ti-device-floppy me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

