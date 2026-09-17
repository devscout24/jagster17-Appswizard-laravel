@extends('layouts.admin')

@section('title', 'Edit Membership Plan - ' . $plan->name)
@section('page_title', 'Edit Plan: ' . $plan->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.plans.index') }}">Plans</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit Plan Specifications</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.plans.update', $plan->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagline / Subtitle</label>
                            <input type="text" name="tagline" class="form-control" value="{{ old('tagline', $plan->tagline) }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monthly Price ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="monthly_price" class="form-control" value="{{ old('monthly_price', $plan->monthly_price) }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Annual Price ($)</label>
                            <input type="number" step="0.01" name="annual_price" class="form-control" value="{{ old('annual_price', $plan->annual_price) }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Badge Label</label>
                            <input type="text" name="badge" class="form-control" value="{{ old('badge', $plan->badge) }}" />
                        </div>
                    </div>

                    <!-- Limits & Capabilities -->
                    <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3 border-top pt-3">Tier Limits (Leave blank for unlimited)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Service Limit</label>
                            <input type="number" name="service_limit" class="form-control" value="{{ old('service_limit', $plan->service_limit) }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gallery Images Limit</label>
                            <input type="number" name="gallery_limit" class="form-control" value="{{ old('gallery_limit', $plan->gallery_limit) }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Video Limit</label>
                            <input type="number" name="video_limit" class="form-control" value="{{ old('video_limit', $plan->video_limit) }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order <span class="text-danger">*</span></label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $plan->sort_order) }}" required />
                        </div>
                        <div class="col-md-6 d-flex align-items-center gap-4 mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_popular" id="is_popular" value="1" {{ old('is_popular', $plan->is_popular) ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_popular">Highlight as Popular</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_active">Active &amp; Selectable</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 border-top pt-3">
                        <label class="form-label">Features Included (1 per line) <span class="text-danger">*</span></label>
                        @php
                            $featuresText = is_array($plan->features) ? implode("\n", $plan->features) : $plan->features;
                        @endphp
                        <textarea name="features" rows="4" class="form-control" required>{{ old('features', $featuresText) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.plans.index') }}" class="btn btn-light">Cancel</a>
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

