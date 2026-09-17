@extends('layouts.admin')

@section('title', 'Nomination - ' . $nomination->nominee_name)
@section('page_title', 'Nomination: ' . $nomination->nominee_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.veteran-nominations.index') }}">Nominations</a></li>
    <li class="breadcrumb-item active">{{ $nomination->nominee_name }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.veteran-nominations.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Nominations
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Story & Status Controls -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                        <i class="ti ti-medal fs-18"></i>
                    </span>
                    <div>
                        <h5 class="card-title mb-0 fw-bold">{{ $nomination->nominee_name }}</h5>
                        <span class="text-primary fs-xs fw-semibold">{{ $nomination->nominee_branch ?? 'U.S. Military Veteran' }}</span>
                    </div>
                </div>
                @php
                    $nBadge = match($nomination->status) {
                        'approved' => 'bg-success-subtle text-success',
                        'reviewing' => 'bg-info-subtle text-info',
                        'pending' => 'bg-warning-subtle text-warning',
                        'rejected' => 'bg-danger-subtle text-danger',
                        default => 'bg-secondary-subtle text-secondary',
                    };
                @endphp
                <span class="badge {{ $nBadge }} text-uppercase fs-sm">{{ $nomination->status }}</span>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="text-muted fs-xs d-block mb-1">Project Needed:</label>
                    <h5 class="fw-bold text-dark">{{ $nomination->project_needed }}</h5>
                </div>

                <div class="mb-4">
                    <label class="text-muted fs-xs d-block mb-1">Nominee Veteran Story &amp; Circumstances:</label>
                    <div class="p-3 bg-light rounded border text-dark fs-base lh-base">
                        {{ $nomination->story_details }}
                    </div>
                </div>

                <div class="row g-3 border-top pt-3">
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Nominee Location:</label>
                        <span class="text-dark fw-medium">{{ $nomination->nominee_city ?? 'N/A' }}, {{ $nomination->nominee_state ?? '' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Submission Date:</label>
                        <span class="text-dark fw-medium">{{ $nomination->created_at?->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Nominator Details & Workflow Actions -->
    <div class="col-xl-4">
        <!-- Status Action Card -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Review Status Action</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.veteran-nominations.update-status', $nomination->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fs-sm">Update Nomination Status:</label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ $nomination->status === 'pending' ? 'selected' : '' }}>Pending Initial Review</option>
                            <option value="reviewing" {{ $nomination->status === 'reviewing' ? 'selected' : '' }}>Under Investigation / Reviewing</option>
                            <option value="approved" {{ $nomination->status === 'approved' ? 'selected' : '' }}>Approved &amp; Award Grant</option>
                            <option value="rejected" {{ $nomination->status === 'rejected' ? 'selected' : '' }}>Rejected / Not Eligible</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-check me-1"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Nominator Info -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Nominator Contact Information</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $nomination->nominator_name }}</h6>
                <ul class="list-unstyled mb-0 fs-sm mt-3">
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Email:</span>
                        <a href="mailto:{{ $nomination->nominator_email }}">{{ $nomination->nominator_email }}</a>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted">Phone:</span>
                        <span>{{ $nomination->nominator_phone ?? 'N/A' }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

