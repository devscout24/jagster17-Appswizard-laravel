@extends('layouts.admin')

@section('title', 'Product Details - ' . $product->name)
@section('page_title', $product->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-edit me-1"></i> Edit Product
    </a>
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Catalog
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Product Image & Overview -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center pt-4">
                <img src="{{ asset($product->image ?? 'assets/images/products/product-1.png') }}" class="img-fluid rounded border p-2 mb-3 bg-light" style="max-height: 220px;" alt="Product" onerror="this.src='{{ asset('assets/images/products/product-1.png') }}'" />
                <h5 class="fw-bold mb-1">{{ $product->name }}</h5>
                <p class="text-muted fs-xs mb-2">SKU: <code>{{ $product->sku ?? 'N/A' }}</code></p>
                <h3 class="text-success fw-bold my-2">${{ number_format($product->price, 2) }}</h3>

                <div class="d-flex justify-content-center gap-1 mb-3">
                    @if($product->in_stock)
                        <span class="badge bg-success-subtle text-success">In Stock ({{ $product->stock_quantity }})</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                    @endif
                    @if($product->is_trending)
                        <span class="badge bg-danger-subtle text-danger"><i class="ti ti-flame"></i> Trending</span>
                    @endif
                    @if($product->is_elite_tier)
                        <span class="badge bg-warning-subtle text-warning"><i class="ti ti-crown"></i> Elite Tier</span>
                    @endif
                </div>
            </div>

            <div class="card-body border-top pt-3">
                <h6 class="fw-semibold mb-3">Seller Details</h6>
                <ul class="list-unstyled mb-0 fs-sm">
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Seller Contractor:</span>
                        <a href="{{ route('admin.contractors.show', $product->business_id) }}" class="fw-semibold text-primary">
                            {{ $product->business?->businessProfile?->business_name ?? $product->business?->name }}
                        </a>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Category:</span>
                        <span class="fw-medium">{{ $product->category?->name ?? 'General' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Warranty:</span>
                        <span class="fw-medium">{{ $product->warranty ?? 'Standard Manufacturer' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Shipping:</span>
                        <span class="fw-medium">{{ $product->shipping_info ?? 'Standard' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Specs & Features -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Product Description &amp; Highlights</h5>
            </div>
            <div class="card-body">
                <p class="text-dark fs-base mb-4">{{ $product->description ?? 'No detailed description available.' }}</p>

                @if(!empty($product->features))
                    <h6 class="fw-semibold mb-2">Key Features &amp; Specifications</h6>
                    <ul class="list-group list-group-flush mb-0">
                        @foreach((array)$product->features as $feature)
                            <li class="list-group-item px-0 py-2 d-flex align-items-center">
                                <i class="ti ti-check text-success me-2 fs-18"></i>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

