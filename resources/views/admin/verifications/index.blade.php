@extends('layouts.admin')

@section('title', 'Verification Queue')
@section('page_title', 'Contractor Verification Queue')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Verification Queue</li>
@endsection

@section('content')
<ul class="nav nav-pills gap-2 mb-3">
    @foreach (['pending' => 'Pending Review', 'verified' => 'Verified', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
        <li class="nav-item">
            <a href="{{ route('admin.verifications.index', ['status' => $value, 'type' => $type, 'search' => $search]) }}"
               class="nav-link {{ $status === $value ? 'active' : 'bg-white border' }}">
                {{ $label }}
                @if ($value !== 'all')
                    <span class="badge {{ $status === $value ? 'bg-white text-primary' : 'bg-light text-dark' }} ms-1">{{ $counts[$value] ?? 0 }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.verifications.index') }}" method="GET" class="row g-2 align-items-center w-100">
            <input type="hidden" name="status" value="{{ $status }}" />
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search contractor or business name..." value="{{ $search }}" />
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All document types</option>
                    @foreach (\App\Models\BusinessDocument::TYPES as $value => $label)
                        <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Contractor</th>
                        <th>Document</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th style="min-width: 320px;">Review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php
                            $badge = match ($document->status) {
                                'verified' => 'bg-success-subtle text-success',
                                'rejected' => 'bg-danger-subtle text-danger',
                                default => 'bg-warning-subtle text-warning',
                            };
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.contractors.show', $document->business_id) }}" class="fw-bold text-primary">
                                    {{ $document->business?->businessProfile?->business_name ?? $document->business?->name }}
                                </a>
                                <div class="fs-xs text-muted">{{ $document->business?->email }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ \App\Models\BusinessDocument::TYPES[$document->type] ?? $document->type }}</div>
                                <a href="{{ $document->file_url }}" target="_blank" rel="noopener" class="fs-xs">
                                    <i class="ti ti-file-text me-1"></i>{{ $document->original_name ?? 'View file' }}
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $badge }} text-uppercase">{{ $document->status }}</span>
                                @if ($document->reviewed_at)
                                    <div class="fs-xs text-muted mt-1">by {{ $document->reviewer?->name ?? 'admin' }}, {{ $document->reviewed_at->format('M d, Y') }}</div>
                                @endif
                                @if ($document->admin_notes)
                                    <div class="fs-xs text-danger mt-1">{{ $document->admin_notes }}</div>
                                @endif
                            </td>
                            <td class="fs-xs text-muted">{{ $document->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <form action="{{ route('admin.verifications.update', $document) }}" method="POST" class="d-flex gap-2 align-items-start">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Reason (required to reject)" value="{{ $document->status === 'rejected' ? $document->admin_notes : '' }}" />
                                    <button type="submit" name="decision" value="verified" class="btn btn-sm btn-success text-nowrap" title="Approve">
                                        <i class="ti ti-check"></i> Verify
                                    </button>
                                    <button type="submit" name="decision" value="rejected" class="btn btn-sm btn-outline-danger text-nowrap" title="Reject">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="ti ti-shield-check fs-2 d-block mb-2"></i>
                                Nothing to review here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($documents->hasPages())
        <div class="card-footer bg-transparent border-top py-3">{{ $documents->links() }}</div>
    @endif
</div>
@endsection
