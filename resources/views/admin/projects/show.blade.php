@extends('layouts.admin')

@section('title', 'Project - ' . $project->title)
@section('page_title', $project->title)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.projects.index') }}">Projects</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.projects.edit', $project->id) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-edit me-1"></i> Edit Project Progress
    </a>
    <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Projects
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Project Overview & Milestones -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">{{ $project->title }}</h5>
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
                <span class="badge {{ $pBadge }} text-uppercase fs-sm">{{ str_replace('_', ' ', $project->status) }}</span>
            </div>
            <div class="card-body">
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold fs-sm">Milestone Completion Progress</span>
                        <span class="fw-bold text-success fs-sm">{{ $project->progress_percent }}%</span>
                    </div>
                    <div class="progress progress-md">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: {{ $project->progress_percent }}%;"></div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="text-muted fs-xs d-block">Contract Value</label>
                        <h4 class="fw-bold text-success mb-0">${{ number_format($project->total_amount ?? 0, 2) }}</h4>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted fs-xs d-block">Start Date</label>
                        <strong class="text-dark fs-sm">{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('M d, Y') : 'Not Set' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <label class="text-muted fs-xs d-block">Estimated Completion</label>
                        <strong class="text-dark fs-sm">{{ $project->due_date ? \Carbon\Carbon::parse($project->due_date)->format('M d, Y') : 'Not Set' }}</strong>
                    </div>
                    <div class="col-md-12 border-top pt-3">
                        <label class="text-muted fs-xs d-block mb-1">Project Scope &amp; Notes</label>
                        <p class="text-dark fs-sm mb-0">{{ $project->description ?? 'No project notes available.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices for this Project -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Linked Project Invoices</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Issued Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($project->invoices as $inv)
                                <tr>
                                    <td><a href="{{ route('admin.invoices.show', $inv->id) }}" class="fw-semibold text-primary">{{ $inv->invoice_number }}</a></td>
                                    <td class="fw-bold text-dark">${{ number_format($inv->amount, 2) }}</td>
                                    <td class="text-muted fs-xs">{{ $inv->issued_at?->format('M d, Y') }}</td>
                                    <td class="text-muted fs-xs">{{ $inv->due_at?->format('M d, Y') }}</td>
                                    <td><span class="badge bg-success-subtle text-success text-uppercase">{{ $inv->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No invoices created for this project yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Contractor & Client Details -->
    <div class="col-xl-4">
        <!-- Client -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Client Information</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <img src="{{ asset($project->customer?->avatar ?? 'assets/images/users/user-5.jpg') }}" class="avatar-sm rounded-circle me-2" alt="Avatar" />
                    <div>
                        <a href="{{ $project->customer ? route('admin.customers.show', $project->customer->id) : '#' }}" class="fw-semibold text-primary d-block">
                            {{ $project->customer?->name ?? 'Client' }}
                        </a>
                        <small class="text-muted">{{ $project->customer?->email }}</small>
                    </div>
                </div>
                <div class="fs-xs text-muted mt-2">
                    <i class="ti ti-phone me-1"></i> {{ $project->customer?->phone ?? 'N/A' }}
                </div>
            </div>
        </div>

        <!-- Contractor -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Contractor / Builder</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <img src="{{ asset($project->business?->avatar ?? 'assets/images/users/user-1.jpg') }}" class="avatar-sm rounded-circle me-2" alt="Avatar" />
                    <div>
                        <a href="{{ route('admin.contractors.show', $project->business_id) }}" class="fw-semibold text-primary d-block">
                            {{ $project->business?->businessProfile?->business_name ?? $project->business?->name }}
                        </a>
                        <small class="text-muted">Owner: {{ $project->business?->name }}</small>
                    </div>
                </div>
                <div class="fs-xs text-muted mt-2">
                    <i class="ti ti-certificate me-1"></i> Lic: {{ $project->business?->businessProfile?->license_number ?? 'N/A' }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

