@extends('layouts.admin')

@section('title', 'Review Details')
@section('page_title', 'Client Review & Testimonial')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.reviews.index') }}">Reviews</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('page_actions')
    <form action="{{ route('admin.reviews.toggle-featured', $review->id) }}" method="POST" class="d-inline-block me-2">
        @csrf
        <button type="submit" class="btn btn-sm {{ $review->is_featured ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
            <i class="ti ti-star me-1"></i> {{ $review->is_featured ? 'Featured on Website' : 'Mark as Featured Testimonial' }}
        </button>
    </form>
    <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to Reviews
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="text-warning fs-5 me-2">
                        @for($s=1; $s<=5; $s++)
                            <i class="ti ti-star{{ $s <= $review->rating ? '-filled' : '' }}"></i>
                        @endfor
                    </span>
                    <strong class="text-dark">{{ $review->rating }} out of 5 Stars</strong>
                </div>
                <small class="text-muted">{{ $review->created_at?->format('M d, Y H:i') }}</small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="text-muted fs-xs d-block mb-1">Customer Review Comment:</label>
                    <div class="p-3 bg-light rounded border text-dark fs-base">
                        "{{ $review->comment }}"
                    </div>
                </div>

                @if($review->contractor_reply)
                    <div class="mb-4">
                        <label class="text-muted fs-xs d-block mb-1">Contractor Official Response:</label>
                        <div class="p-3 bg-primary-subtle rounded border border-primary-subtle text-dark fs-sm">
                            <i class="ti ti-corner-down-right text-primary me-1"></i>
                            {{ $review->contractor_reply }}
                            <div class="text-muted fs-xs mt-2 text-end">Replied: {{ $review->replied_at ? \Carbon\Carbon::parse($review->replied_at)->format('M d, Y') : '' }}</div>
                        </div>
                    </div>
                @endif

                <div class="row g-3 border-top pt-3">
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Contractor Evaluated:</label>
                        <a href="{{ route('admin.contractors.show', $review->business_id) }}" class="fw-semibold text-primary">
                            {{ $review->business?->businessProfile?->business_name ?? $review->business?->name }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted fs-xs d-block">Reviewing Client:</label>
                        <span class="fw-semibold text-dark">{{ $review->customer?->name ?? 'Homeowner' }}</span>
                        <small class="text-muted d-block">{{ $review->customer?->email }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

