@extends('layouts.admin')

@section('title', 'Quote Requests & Leads')
@section('page_title', 'Quote Requests Pipeline')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Quotes &amp; Leads</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.quotes.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search ref, project, client, city..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Statuses</option>
                    <option value="new" {{ $status === 'new' ? 'selected' : '' }}>New</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="quoted" {{ $status === 'quoted' ? 'selected' : '' }}>Quoted</option>
                    <option value="accepted" {{ $status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="declined" {{ $status === 'declined' ? 'selected' : '' }}>Declined</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Service Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string)$categoryId === (string)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select name="timeline" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Timelines</option>
                    <option value="asap" {{ $timeline === 'asap' ? 'selected' : '' }}>ASAP</option>
                    <option value="within_1_week" {{ $timeline === 'within_1_week' ? 'selected' : '' }}>Within 1 Week</option>
                    <option value="within_1_month" {{ $timeline === 'within_1_month' ? 'selected' : '' }}>Within 1 Month</option>
                    <option value="flexible" {{ $timeline === 'flexible' ? 'selected' : '' }}>Flexible</option>
                </select>
            </div>

            <div class="col-md-1 text-end">
                <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm btn-light w-100" title="Reset Filters">
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
                        <th>Reference #</th>
                        <th>Project &amp; Category</th>
                        <th>Homeowner / Client</th>
                        <th>Contractor</th>
                        <th>Budget / Quote</th>
                        <th>Timeline</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotes as $quote)
                        <tr>
                            <td>
                                <a href="{{ route('admin.quotes.show', $quote->id) }}" class="fw-bold text-primary">
                                    {{ $quote->reference_number }}
                                </a>
                                <div class="fs-xs text-muted">{{ $quote->requested_at?->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <strong class="d-block text-dark">{{ Str::limit($quote->project_title, 40) }}</strong>
                                <small class="text-muted">{{ $quote->category?->name ?? 'Service Request' }}</small>
                            </td>
                            <td>
                                <div>{{ $quote->customer?->name ?? 'Guest Homeowner' }}</div>
                                <small class="text-muted">{{ $quote->city ?? 'N/A' }}, {{ $quote->state ?? '' }}</small>
                            </td>
                            <td>
                                @if($quote->business)
                                    <a href="{{ route('admin.contractors.show', $quote->business_id) }}" class="fw-medium text-dark">
                                        {{ $quote->business->businessProfile?->business_name ?? $quote->business->name }}
                                    </a>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @if($quote->quote_amount)
                                    <span class="fw-bold text-success">${{ number_format($quote->quote_amount, 2) }}</span>
                                    <div class="fs-xs text-muted">Quoted Amount</div>
                                @elseif($quote->budget_min || $quote->budget_max)
                                    <span class="fw-medium text-dark">${{ number_format($quote->budget_min) }} - ${{ number_format($quote->budget_max) }}</span>
                                    <div class="fs-xs text-muted">Client Budget</div>
                                @else
                                    <span class="text-muted fs-xs">Flexible Budget</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-uppercase fs-xs">
                                    {{ str_replace('_', ' ', $quote->timeline ?? 'flexible') }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $badge = match($quote->status) {
                                        'accepted' => 'bg-success-subtle text-success',
                                        'quoted' => 'bg-info-subtle text-info',
                                        'new', 'pending' => 'bg-warning-subtle text-warning',
                                        'declined', 'rejected', 'expired' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badge }} text-uppercase">{{ $quote->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.quotes.show', $quote->id) }}" class="btn btn-sm btn-icon btn-light" title="View Quote">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('admin.quotes.edit', $quote->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit / Assign Quote">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('admin.quotes.destroy', $quote->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this quote request?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Quote">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No quote requests recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($quotes->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $quotes->links() }}
        </div>
    @endif
</div>
@endsection

