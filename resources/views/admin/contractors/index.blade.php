@extends('layouts.admin')

@section('title', 'Contractor Management')
@section('page_title', 'Contractors & Service Providers')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Contractors</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.contractors.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search name, email, license, city..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="is_elite" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Elite Filter</option>
                    <option value="1" {{ $isElite === '1' ? 'selected' : '' }}>Elite Tier Only</option>
                    <option value="0" {{ $isElite === '0' ? 'selected' : '' }}>Standard Tier</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="is_id_verified" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">ID Verification</option>
                    <option value="1" {{ $isVerified === '1' ? 'selected' : '' }}>ID.me Verified</option>
                    <option value="0" {{ $isVerified === '0' ? 'selected' : '' }}>Unverified</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="is_veteran_owned" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Veteran Owned</option>
                    <option value="1" {{ $isVeteran === '1' ? 'selected' : '' }}>Veteran Owned</option>
                    <option value="0" {{ $isVeteran === '0' ? 'selected' : '' }}>Non-Veteran</option>
                </select>
            </div>

            <div class="col-md-1 text-end">
                <a href="{{ route('admin.contractors.index') }}" class="btn btn-sm btn-light w-100" title="Reset Filters">
                    <i class="ti ti-refresh"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Contractor / Business</th>
                        <th>Location &amp; Contact</th>
                        <th>Credentials &amp; Badges</th>
                        <th>Activity Summary</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contractors as $contractor)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset($contractor->avatar ?? 'assets/images/users/user-1.jpg') }}" class="avatar-sm rounded-circle me-2" alt="Avatar" />
                                    <div>
                                        <a href="{{ route('admin.contractors.show', $contractor->id) }}" class="fw-semibold text-primary d-block">
                                            {{ $contractor->businessProfile?->business_name ?? $contractor->name }}
                                        </a>
                                        <small class="text-muted">Owner: {{ $contractor->name }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="ti ti-map-pin fs-xs text-muted me-1"></i>{{ $contractor->businessProfile?->city ?? 'N/A' }}, {{ $contractor->businessProfile?->state ?? '' }}</div>
                                <div class="fs-xs text-muted"><i class="ti ti-phone fs-xs me-1"></i>{{ $contractor->phone ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($contractor->businessProfile?->is_elite)
                                        <span class="badge bg-warning-subtle text-warning"><i class="ti ti-crown"></i> Elite</span>
                                    @endif
                                    @if($contractor->businessProfile?->is_id_verified)
                                        <span class="badge bg-success-subtle text-success"><i class="ti ti-shield-check"></i> ID.me</span>
                                    @endif
                                    @if($contractor->businessProfile?->is_veteran_owned)
                                        <span class="badge bg-primary-subtle text-primary"><i class="ti ti-medal"></i> Veteran</span>
                                    @endif
                                    @if($contractor->businessProfile?->license_number)
                                        <span class="badge bg-info-subtle text-info fs-xs" title="License: {{ $contractor->businessProfile->license_number }}">
                                            <i class="ti ti-certificate"></i> Lic #{{ Str::limit($contractor->businessProfile->license_number, 8) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="fs-xs">
                                    <span class="fw-semibold text-dark">{{ $contractor->quote_requests_count }}</span> quotes,
                                    <span class="fw-semibold text-dark">{{ $contractor->projects_count }}</span> projects
                                </div>
                                <div class="fs-xs text-muted">
                                    <span class="text-warning"><i class="ti ti-star-filled"></i> {{ $contractor->businessProfile?->avg_rating ?? '0.0' }}</span>
                                    ({{ $contractor->reviews_count }} reviews)
                                </div>
                            </td>
                            <td>
                                @if($contractor->status === 'active')
                                    <span class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger text-uppercase">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.contractors.show', $contractor->id) }}">
                                                <i class="ti ti-eye me-2 text-muted"></i> View Full Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.contractors.edit', $contractor->id) }}">
                                                <i class="ti ti-edit me-2 text-muted"></i> Edit Profile &amp; Badges
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.contractors.destroy', $contractor->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this contractor?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ti ti-trash me-2"></i> Delete Account
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="ti ti-tool fs-36 d-block mb-2 text-muted"></i>
                                No contractors found matching the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($contractors->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $contractors->links() }}
        </div>
    @endif
</div>
@endsection

