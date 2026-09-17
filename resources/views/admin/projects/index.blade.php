@extends('layouts.admin')

@section('title', 'Client Projects')
@section('page_title', 'Client Projects & Milestones')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Projects</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.projects.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search project title, client, contractor..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Project Statuses</option>
                    <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="scheduled" {{ $status === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="pending_materials" {{ $status === 'pending_materials' ? 'selected' : '' }}>Pending Materials</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Project Title</th>
                        <th>Client</th>
                        <th>Contractor</th>
                        <th>Progress</th>
                        <th>Total Value</th>
                        <th>Timeline (Start / Due)</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr>
                            <td>
                                <a href="{{ route('admin.projects.show', $project->id) }}" class="fw-semibold text-primary d-block">
                                    {{ $project->title }}
                                </a>
                                @if($project->quoteRequest)
                                    <small class="text-muted">Ref: {{ $project->quoteRequest->reference_number }}</small>
                                @endif
                            </td>
                            <td>{{ $project->customer?->name ?? 'Client' }}</td>
                            <td>
                                <a href="{{ route('admin.contractors.show', $project->business_id) }}" class="fw-medium text-dark">
                                    {{ $project->business?->businessProfile?->business_name ?? $project->business?->name }}
                                </a>
                            </td>
                            <td style="min-width: 130px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress progress-sm flex-grow-1">
                                        <div class="progress-bar bg-success" style="width: {{ $project->progress_percent }}%;"></div>
                                    </div>
                                    <span class="fs-xs fw-semibold">{{ $project->progress_percent }}%</span>
                                </div>
                            </td>
                            <td>
                                <strong class="text-success">${{ number_format($project->total_amount ?? 0, 2) }}</strong>
                            </td>
                            <td>
                                <div class="fs-xs text-muted">
                                    <span>Start: {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('M d, Y') : 'N/A' }}</span>
                                    <span class="d-block">Due: {{ $project->due_date ? \Carbon\Carbon::parse($project->due_date)->format('M d, Y') : 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $pBadge = match($project->status) {
                                        'completed' => 'bg-success-subtle text-success',
                                        'in_progress' => 'bg-primary-subtle text-primary',
                                        'scheduled' => 'bg-info-subtle text-info',
                                        'pending_materials' => 'bg-warning-subtle text-warning',
                                        'cancelled' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $pBadge }} text-uppercase">{{ str_replace('_', ' ', $project->status) }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.projects.show', $project->id) }}" class="btn btn-sm btn-icon btn-light" title="View Project">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('admin.projects.edit', $project->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit Progress">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('admin.projects.destroy', $project->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Project">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No projects found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($projects->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $projects->links() }}
        </div>
    @endif
</div>
@endsection

