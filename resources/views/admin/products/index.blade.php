@extends('layouts.admin')

@section('title', 'Marketplace Products')
@section('page_title', 'Marketplace Catalog & Products')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Products</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus me-1"></i> Add Product
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.products.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search product, SKU, seller..." value="{{ $search }}" />
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
                <select name="in_stock" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Stock Status</option>
                    <option value="1" {{ $inStock === '1' ? 'selected' : '' }}>In Stock Only</option>
                    <option value="0" {{ $inStock === '0' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="off" {{ $status === 'off' ? 'selected' : '' }}>Off / Hidden</option>
                </select>
            </div>

            <div class="col-md-2 text-end">
                <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Product &amp; SKU</th>
                        <th>Seller (Contractor)</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Inventory / Stock</th>
                        <th>Badges</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset($product->image ?? 'assets/images/products/product-1.png') }}" class="avatar-sm rounded border me-2 object-fit-cover" alt="Product" onerror="this.src='{{ asset('assets/images/products/product-1.png') }}'" />
                                    <div>
                                        <a href="{{ route('admin.products.show', $product->id) }}" class="fw-semibold text-primary d-block">
                                            {{ $product->name }}
                                        </a>
                                        <small class="text-muted">SKU: <code>{{ $product->sku ?? 'N/A' }}</code></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('admin.contractors.show', $product->business_id) }}" class="fw-medium text-dark">
                                    {{ $product->business?->businessProfile?->business_name ?? $product->business?->name }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $product->category?->name ?? 'General' }}</span>
                            </td>
                            <td>
                                <strong class="text-success">${{ number_format($product->price, 2) }}</strong>
                            </td>
                            <td>
                                @if($product->in_stock && $product->stock_quantity > 0)
                                    <span class="badge bg-success-subtle text-success">{{ $product->stock_quantity }} units</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @if($product->is_trending)
                                        <span class="badge bg-danger-subtle text-danger"><i class="ti ti-flame"></i> Hot</span>
                                    @endif
                                    @if($product->is_elite_tier)
                                        <span class="badge bg-warning-subtle text-warning"><i class="ti ti-crown"></i> Elite</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $product->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} text-uppercase">
                                    {{ $product->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-sm btn-icon btn-light" title="View Details">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit Product">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Product">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No marketplace products listed.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($products->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection

