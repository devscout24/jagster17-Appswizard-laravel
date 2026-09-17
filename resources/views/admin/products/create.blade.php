@extends('layouts.admin')

@section('title', 'List New Marketplace Product')
@section('page_title', 'Add Marketplace Product')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Product Specifications</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Seller / Contractor <span class="text-danger">*</span></label>
                            <select name="business_id" class="form-select" required>
                                <option value="">Select Contractor...</option>
                                @foreach($contractors as $c)
                                    <option value="{{ $c->id }}" {{ old('business_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->businessProfile?->business_name ?? $c->name }} ({{ $c->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Product Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Smart WiFi Digital Thermostat Pro" value="{{ old('name') }}" required />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">SKU Number</label>
                            <input type="text" name="sku" class="form-control" placeholder="e.g. PRD-THM-900" value="{{ old('sku') }}" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price') }}" required />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="each, box, bundle" value="{{ old('unit', 'each') }}" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', 10) }}" required />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Warranty Details</label>
                            <input type="text" name="warranty" class="form-control" placeholder="e.g. 3 Years Manufacturer Warranty" value="{{ old('warranty') }}" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Shipping Information</label>
                            <input type="text" name="shipping_info" class="form-control" placeholder="e.g. Free 2-day delivery" value="{{ old('shipping_info') }}" />
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Product Features (1 per line)</label>
                            <textarea name="features" rows="3" class="form-control" placeholder="High Energy Efficiency&#10;Smart App Integration&#10;Industrial Grade">{{ old('features') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Full Product Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Detailed product specifications...">{{ old('description') }}</textarea>
                        </div>
                    </div>

                    <!-- Badges & Visibility -->
                    <div class="row g-3 mb-4 bg-light p-3 rounded border">
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="in_stock" id="in_stock" value="1" {{ old('in_stock', '1') ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="in_stock">In Stock for Order</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_trending" id="is_trending" value="1" {{ old('is_trending') ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_trending">
                                    <i class="ti ti-flame text-danger"></i> Trending / Featured
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_elite_tier" id="is_elite_tier" value="1" {{ old('is_elite_tier') ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_elite_tier">
                                    <i class="ti ti-crown text-warning"></i> Elite Tier Item
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <label class="form-label">Catalog Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active (Public in Marketplace)</option>
                                <option value="off" {{ old('status') === 'off' ? 'selected' : '' }}>Off (Hidden / Draft)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-plus me-1"></i> List Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

