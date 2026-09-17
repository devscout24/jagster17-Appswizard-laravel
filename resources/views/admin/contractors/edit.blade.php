@extends('layouts.admin')

@section('title', 'Edit Contractor - ' . ($contractor->businessProfile?->business_name ?? $contractor->name))
@section('page_title', 'Edit Contractor Profile')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.contractors.index') }}">Contractors</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.contractors.show', $contractor->id) }}">{{ $contractor->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom">
        <h5 class="card-title mb-0 fw-semibold">Contractor Details &amp; Verification Badges</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.contractors.update', $contractor->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Section 1: User Account & Status -->
            <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3">1. Account Credentials &amp; Status</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Contact / Owner Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $contractor->name) }}" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $contractor->email) }}" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', $contractor->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $contractor->status) === 'inactive' ? 'selected' : '' }}>Inactive / Suspended</option>
                    </select>
                </div>
            </div>

            <!-- Section 2: Business & Verification Badges -->
            <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3 border-top pt-3">2. Business Profile &amp; Verification Badges</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Company / Business Name <span class="text-danger">*</span></label>
                    <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $contractor->businessProfile?->business_name) }}" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Business Type</label>
                    <input type="text" name="business_type" class="form-control" placeholder="LLC, Sole Proprietor, Corporation" value="{{ old('business_type', $contractor->businessProfile?->business_type) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Business Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $contractor->phone) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">State License Number</label>
                    <input type="text" name="license_number" class="form-control" placeholder="e.g. ROC-329841" value="{{ old('license_number', $contractor->businessProfile?->license_number) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tax ID (EIN / SSN)</label>
                    <input type="text" name="tax_id" class="form-control" value="{{ old('tax_id', $contractor->businessProfile?->tax_id) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hourly Rate ($)</label>
                    <input type="number" step="0.01" name="hourly_rate" class="form-control" value="{{ old('hourly_rate', $contractor->businessProfile?->hourly_rate) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Years of Experience</label>
                    <input type="number" name="years_experience" class="form-control" value="{{ old('years_experience', $contractor->businessProfile?->years_experience) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Service Radius (Miles)</label>
                    <input type="number" name="service_radius" class="form-control" value="{{ old('service_radius', $contractor->businessProfile?->service_radius ?? 25) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Business Hours</label>
                    <input type="text" name="business_hours" class="form-control" value="{{ old('business_hours', $contractor->businessProfile?->business_hours) }}" />
                </div>
            </div>

            <!-- Verification Checkbox Badges -->
            <div class="row g-3 mb-4 bg-light p-3 rounded border">
                <div class="col-md-12">
                    <label class="form-label fw-bold mb-2">Verification &amp; Badges Controls:</label>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_elite" id="is_elite" value="1" {{ old('is_elite', $contractor->businessProfile?->is_elite) ? 'checked' : '' }} />
                        <label class="form-check-label fw-semibold" for="is_elite">
                            <i class="ti ti-crown text-warning me-1"></i> Elite Tier Member
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_id_verified" id="is_id_verified" value="1" {{ old('is_id_verified', $contractor->businessProfile?->is_id_verified) ? 'checked' : '' }} />
                        <label class="form-check-label fw-semibold" for="is_id_verified">
                            <i class="ti ti-shield-check text-success me-1"></i> ID.me Identity Verified
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_veteran_owned" id="is_veteran_owned" value="1" {{ old('is_veteran_owned', $contractor->businessProfile?->is_veteran_owned) ? 'checked' : '' }} />
                        <label class="form-check-label fw-semibold" for="is_veteran_owned">
                            <i class="ti ti-medal text-primary me-1"></i> Veteran Owned Business
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_license_verified" id="is_license_verified" value="1" {{ old('is_license_verified', $contractor->businessProfile?->is_license_verified) ? 'checked' : '' }} />
                        <label class="form-check-label fw-semibold" for="is_license_verified">
                            <i class="ti ti-certificate text-info me-1"></i> State License Verified
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_available_today" id="is_available_today" value="1" {{ old('is_available_today', $contractor->businessProfile?->is_available_today) ? 'checked' : '' }} />
                        <label class="form-check-label fw-semibold" for="is_available_today">
                            <i class="ti ti-clock-check text-success me-1"></i> Available Today
                        </label>
                    </div>
                </div>
            </div>

            <!-- Location Details -->
            <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3 border-top pt-3">3. Location &amp; Bio</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $contractor->businessProfile?->city) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="{{ old('state', $contractor->businessProfile?->state) }}" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Zip Code</label>
                    <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code', $contractor->businessProfile?->zip_code) }}" />
                </div>
                <div class="col-md-12">
                    <label class="form-label">Company Bio / Description</label>
                    <textarea name="bio" rows="4" class="form-control">{{ old('bio', $contractor->businessProfile?->bio) }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="{{ route('admin.contractors.show', $contractor->id) }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ti ti-device-floppy me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

