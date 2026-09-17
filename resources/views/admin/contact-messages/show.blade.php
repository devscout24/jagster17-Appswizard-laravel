@extends('layouts.admin')

@section('title', 'Inquiry - ' . $message->subject)
@section('page_title', 'Inquiry: ' . $message->subject)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.contact-messages.index') }}">Support</a></li>
    <li class="breadcrumb-item active">View</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Inquiries
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Message Content -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">{{ $message->subject }}</h5>
                <span class="fs-xs text-muted">{{ $message->created_at?->format('M d, Y H:i') }}</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-light rounded border text-dark fs-base lh-base mb-4">
                    {{ $message->message }}
                </div>

                <div class="d-flex gap-2 border-top pt-3">
                    <a href="mailto:{{ $message->email }}?subject=Re: {{ urlencode($message->subject) }}" class="btn btn-primary">
                        <i class="ti ti-mail-forward me-1"></i> Reply via Email
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sender & Status Sidebar -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Update Ticket Status</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.contact-messages.update-status', $message->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fs-sm">Status:</label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ $message->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ $message->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $message->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-check me-1"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Sender Profile</h6>
            </div>
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $message->full_name }}</h6>
                <p class="text-muted fs-sm mb-2">{{ $message->email }}</p>

                @if($message->user)
                    <div class="badge bg-success-subtle text-success">Registered User (ID: #{{ $message->user_id }})</div>
                @else
                    <div class="badge bg-light text-muted border">Guest Inquirer</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

