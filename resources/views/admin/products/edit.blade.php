@extends('layouts.admin')

@section('title', 'Edit Product - ' . $product->name)
@section('page_title', 'Edit Marketplace Product')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.products.show', $product->id) }}">{{ $product->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit Product Specifications</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.update', $product->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Seller / Contractor <span class="text-danger">*</span></label>
                            <select name="business_id" class="form-select" required>
                                @foreach($contractors as $c)
                                    <option value="{{ $c->id }}" {{ old('business_id', $product->business_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->businessProfile?->business_name ?? $c->name }} ({{ $c->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Product Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">SKU Number</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" value="{{ old('unit', $product->unit) }}" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', $product->stock_quantity) }}" required />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Warranty Details</label>
                            <input type="text" name="warranty" class="form-control" value="{{ old('warranty', $product->warranty) }}" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shipping Information</label>
                            <input type="text" name="shipping_info" class="form-control" value="{{ old('shipping_info', $product->shipping_info) }}" />
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Product Features (1 per line)</label>
                            @php
                                $featuresText = is_array($product->features) ? implode("\n", $product->features) : $product->features;
                            @endphp
                            <textarea name="features" rows="3" class="form-control">{{ old('features', $featuresText) }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Full Product Description</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>

                    <!-- Badges & Visibility -->
                    <div class="row g-3 mb-4 bg-light p-3 rounded border">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="in_stock" id="in_stock" value="1" {{ old('in_stock', $product->in_stock) ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="in_stock">In Stock for Order</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending" value="1" {{ old('is_trending', $product->is_trending) ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_trending">
                                    <i class="ti ti-flame text-danger"></i> Trending / Featured
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_elite_tier" id="is_elite_tier" value="1" {{ old('is_elite_tier', $product->is_elite_tier) ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_elite_tier">
                                    <i class="ti ti-crown text-warning"></i> Elite Tier Item
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label class="form-label">Catalog Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Active (Public in Marketplace)</option>
                                <option value="off" {{ old('status', $product->status) === 'off' ? 'selected' : '' }}>Off (Hidden / Draft)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.products.show', $product->id) }}" class="btn btn-light">Cancel</a>
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

