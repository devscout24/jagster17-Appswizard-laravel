@extends('layouts.admin')

@section('title', 'Create Membership Plan')
@section('page_title', 'Create Membership Plan')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Plan Details &amp; Pricing</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.plans.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Pro Verified Plan" value="{{ old('name') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagline / Subtitle</label>
                            <input type="text" name="tagline" class="form-control" placeholder="e.g. Perfect for established contractors" value="{{ old('tagline') }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monthly Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="monthly_price" class="form-control" value="{{ old('monthly_price', 0) }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Annual Price ($)</label>
                            <input type="number" step="0.01" name="annual_price" class="form-control" value="{{ old('annual_price', 0) }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Badge Label</label>
                            <input type="text" name="badge" class="form-control" placeholder="e.g. MOST POPULAR" value="{{ old('badge') }}" />
                        </div>
                    </div>

                    <!-- Limits & Capabilities -->
                    <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3 border-top pt-3">Tier Limits (Leave blank for unlimited)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Service Limit</label>
                            <input type="number" name="service_limit" class="form-control" placeholder="e.g. 5" value="{{ old('service_limit') }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gallery Images Limit</label>
                            <input type="number" name="gallery_limit" class="form-control" placeholder="e.g. 15" value="{{ old('gallery_limit') }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Video Limit</label>
                            <input type="number" name="video_limit" class="form-control" placeholder="e.g. 2" value="{{ old('video_limit') }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order <span class="text-danger">*</span></label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 1) }}" required />
                        </div>
                        <div class="col-md-6 d-flex align-items-center gap-4 mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_popular" id="is_popular" value="1" {{ old('is_popular') ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_popular">Highlight as Popular</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_active">Active &amp; Selectable</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 border-top pt-3">
                        <label class="form-label">Features Included (1 per line) <span class="text-danger">*</span></label>
                        <textarea name="features" rows="4" class="form-control" placeholder="Verified Contractor Badge&#10;Unlimited Quote Leads&#10;Direct Messaging Access&#10;Priority Directory Ranking" required>{{ old('features') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.plans.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-plus me-1"></i> Create Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

