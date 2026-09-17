@extends('layouts.admin')

@section('title', 'Edit Service - ' . $service->name)
@section('page_title', 'Edit Service')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Services</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.services.show', $service->id) }}">{{ $service->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit Service Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.services.update', $service->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $service->name) }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $service->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pricing Type <span class="text-danger">*</span></label>
                            <select name="pricing_type" class="form-select" required>
                                <option value="fixed" {{ old('pricing_type', $service->pricing_type) === 'fixed' ? 'selected' : '' }}>Fixed Price</option>
                                <option value="hourly" {{ old('pricing_type', $service->pricing_type) === 'hourly' ? 'selected' : '' }}>Hourly Rate</option>
                                <option value="sqft" {{ old('pricing_type', $service->pricing_type) === 'sqft' ? 'selected' : '' }}>Per Square Foot</option>
                                <option value="custom_quote" {{ old('pricing_type', $service->pricing_type) === 'custom_quote' ? 'selected' : '' }}>Custom Quote</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $service->price) }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="per hour, per sqft, each" value="{{ old('unit', $service->unit) }}" />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', $service->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $service->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $service->description) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.services.show', $service->id) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

