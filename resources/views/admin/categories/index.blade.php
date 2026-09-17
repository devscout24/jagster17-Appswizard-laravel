@extends('layouts.admin')

@section('title', 'Service & Product Categories')
@section('page_title', 'Taxonomy & Categories')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Categories</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus me-1"></i> Add Category
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.categories.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search category name, description..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Category Types</option>
                    <option value="service" {{ $type === 'service' ? 'selected' : '' }}>Service Categories</option>
                    <option value="product" {{ $type === 'product' ? 'selected' : '' }}>Product Categories</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Category Name</th>
                        <th>Type</th>
                        <th>Icon</th>
                        <th>Linked Items</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar-sm rounded bg-light text-primary d-flex align-items-center justify-content-center me-2">
                                        <i class="{{ $cat->icon ?? 'ti ti-tag' }} fs-18"></i>
                                    </span>
                                    <strong class="text-dark">{{ $cat->name }}</strong>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $cat->type === 'service' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }} text-uppercase">
                                    {{ $cat->type }}
                                </span>
                            </td>
                            <td><code>{{ $cat->icon ?? 'ti ti-tag' }}</code></td>
                            <td>
                                @if($cat->type === 'service')
                                    <span class="badge bg-light text-dark border">{{ $cat->services_count }} services</span>
                                @else
                                    <span class="badge bg-light text-dark border">{{ $cat->products_count }} products</span>
                                @endif
                            </td>
                            <td class="text-muted fs-sm">{{ Str::limit($cat->description, 60) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.categories.edit', $cat->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit Category">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Category">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($categories->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $categories->links() }}
        </div>
    @endif
</div>
@endsection

