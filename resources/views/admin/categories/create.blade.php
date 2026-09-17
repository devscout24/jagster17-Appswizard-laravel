@extends('layouts.admin')

@section('title', 'Create Category')
@section('page_title', 'Create New Category')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Categories</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Category Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Roofing & Siding" value="{{ old('name') }}" required />
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="service" {{ old('type') === 'service' ? 'selected' : '' }}>Service Category</option>
                                <option value="product" {{ old('type') === 'product' ? 'selected' : '' }}>Product Category</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Icon Class (Tabler Icons)</label>
                            <input type="text" name="icon" class="form-control" placeholder="e.g. ti ti-home" value="{{ old('icon', 'ti ti-category') }}" />
                            <small class="text-muted fs-xs">Example: <code>ti ti-home</code>, <code>ti ti-tool</code>, <code>ti ti-bolt</code>, <code>ti ti-hammer</code></small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Brief summary of services or products under this category...">{{ old('description') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-plus me-1"></i> Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

