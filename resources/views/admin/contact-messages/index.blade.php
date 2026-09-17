@extends('layouts.admin')

@section('title', 'Support Inquiries & Tickets')
@section('page_title', 'Support Messages & Contact Inquiries')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Support Inquiries</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.contact-messages.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search sender, email, subject, message..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Inquiry Statuses</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Sender Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Snippet</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $msg)
                        <tr>
                            <td>
                                <a href="{{ route('admin.contact-messages.show', $msg->id) }}" class="fw-bold text-primary">
                                    {{ $msg->full_name }}
                                </a>
                            </td>
                            <td>{{ $msg->email }}</td>
                            <td>
                                <strong class="text-dark">{{ Str::limit($msg->subject, 30) }}</strong>
                            </td>
                            <td>
                                <span class="text-muted d-inline-block text-truncate" style="max-width: 250px;">{{ $msg->message }}</span>
                            </td>
                            <td>
                                @php
                                    $mBadge = match($msg->status) {
                                        'resolved' => 'bg-success-subtle text-success',
                                        'in_progress' => 'bg-info-subtle text-info',
                                        'pending' => 'bg-warning-subtle text-warning',
                                        default => 'bg-secondary-subtle text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $mBadge }} text-uppercase">{{ str_replace('_', ' ', $msg->status) }}</span>
                            </td>
                            <td class="fs-xs text-muted">
                                {{ $msg->created_at?->format('M d, Y H:i') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.contact-messages.show', $msg->id) }}" class="btn btn-sm btn-icon btn-light" title="View Message">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <form action="{{ route('admin.contact-messages.destroy', $msg->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Message">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No support messages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($messages->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $messages->links() }}
        </div>
    @endif
</div>
@endsection

