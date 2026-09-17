@extends('layouts.admin')

@section('title', 'Edit Category - ' . $category->name)
@section('page_title', 'Edit Category')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Categories</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit Category: {{ $category->name }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required />
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="service" {{ old('type', $category->type) === 'service' ? 'selected' : '' }}>Service Category</option>
                                <option value="product" {{ old('type', $category->type) === 'product' ? 'selected' : '' }}>Product Category</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Icon Class (Tabler Icons)</label>
                            <input type="text" name="icon" class="form-control" value="{{ old('icon', $category->icon) }}" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $category->description) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-light">Cancel</a>
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

