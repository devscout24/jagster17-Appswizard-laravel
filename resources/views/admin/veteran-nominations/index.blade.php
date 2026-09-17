@extends('layouts.admin')

@section('title', 'Veteran Grant Nominations')
@section('page_title', 'Giving Back — Veteran Home Repair Grant Nominations')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Veteran Nominations</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.veteran-nominations.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search nominee, branch, project, nominator..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Nomination Statuses</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Review</option>
                    <option value="reviewing" {{ $status === 'reviewing' ? 'selected' : '' }}>Under Investigation</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved / Funded</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.veteran-nominations.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Nominee Name</th>
                        <th>Military Branch</th>
                        <th>Project Needed</th>
                        <th>Location</th>
                        <th>Nominator</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nominations as $nomination)
                        <tr>
                            <td>
                                <a href="{{ route('admin.veteran-nominations.show', $nomination->id) }}" class="fw-bold text-primary">
                                    {{ $nomination->nominee_name }}
                                </a>
                                <div class="fs-xs text-muted">{{ $nomination->created_at?->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">
                                    <i class="ti ti-medal me-1"></i> {{ $nomination->nominee_branch ?? 'Veteran' }}
                                </span>
                            </td>
                            <td>
                                <strong class="text-dark">{{ Str::limit($nomination->project_needed, 35) }}</strong>
                            </td>
                            <td>
                                <span><i class="ti ti-map-pin fs-xs text-muted me-1"></i>{{ $nomination->nominee_city ?? 'N/A' }}, {{ $nomination->nominee_state ?? '' }}</span>
                            </td>
                            <td>
                                <div>{{ $nomination->nominator_name }}</div>
                                <small class="text-muted">{{ $nomination->nominator_email }}</small>
                            </td>
                            <td>
                                @php
                                    $nBadge = match($nomination->status) {
                                        'approved' => 'bg-success-subtle text-success',
                                        'reviewing' => 'bg-info-subtle text-info',
                                        'pending' => 'bg-warning-subtle text-warning',
                                        'rejected' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $nBadge }} text-uppercase">{{ $nomination->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.veteran-nominations.show', $nomination->id) }}" class="btn btn-sm btn-icon btn-light" title="View Full Story">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <form action="{{ route('admin.veteran-nominations.destroy', $nomination->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this nomination?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Nomination">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No veteran nominations recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($nominations->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $nominations->links() }}
        </div>
    @endif
</div>
@endsection

