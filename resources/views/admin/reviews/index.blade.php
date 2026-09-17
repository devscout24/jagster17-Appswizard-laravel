@extends('layouts.admin')

@section('title', 'Client Reviews & Ratings')
@section('page_title', 'Reviews & Testimonials Moderation')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Reviews</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.reviews.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search customer comment, contractor reply..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Star Ratings</option>
                    <option value="5" {{ $rating === '5' ? 'selected' : '' }}>5 Stars</option>
                    <option value="4" {{ $rating === '4' ? 'selected' : '' }}>4 Stars</option>
                    <option value="3" {{ $rating === '3' ? 'selected' : '' }}>3 Stars</option>
                    <option value="2" {{ $rating === '2' ? 'selected' : '' }}>2 Stars</option>
                    <option value="1" {{ $rating === '1' ? 'selected' : '' }}>1 Star</option>
                </select>
            </div>

            <div class="col-md-2">
                <select name="is_featured" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Featured Filter</option>
                    <option value="1" {{ $featured === '1' ? 'selected' : '' }}>Featured Only</option>
                    <option value="0" {{ $featured === '0' ? 'selected' : '' }}>Standard</option>
                </select>
            </div>

            <div class="col-md-2 text-end">
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
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
                        <th>Contractor</th>
                        <th>Homeowner</th>
                        <th>Rating</th>
                        <th>Comment Snippet</th>
                        <th>Featured</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td>
                                <a href="{{ route('admin.contractors.show', $review->business_id) }}" class="fw-semibold text-primary">
                                    {{ $review->business?->businessProfile?->business_name ?? $review->business?->name }}
                                </a>
                            </td>
                            <td>
                                <div>{{ $review->customer?->name ?? 'Verified Homeowner' }}</div>
                                <small class="text-muted">{{ $review->customer?->email }}</small>
                            </td>
                            <td>
                                <span class="text-warning fw-bold">
                                    @for($s=1; $s<=5; $s++)
                                        <i class="ti ti-star{{ $s <= $review->rating ? '-filled' : '' }}"></i>
                                    @endfor
                                    <span class="text-dark ms-1">({{ $review->rating }})</span>
                                </span>
                            </td>
                            <td>
                                <span class="text-dark d-inline-block text-truncate" style="max-width: 280px;" title="{{ $review->comment }}">
                                    {{ $review->comment ?? 'No written comment' }}
                                </span>
                                @if($review->contractor_reply)
                                    <div class="fs-xs text-muted">
                                        <i class="ti ti-corner-down-right me-1 text-primary"></i> Has contractor reply
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($review->is_featured)
                                    <span class="badge bg-warning text-dark"><i class="ti ti-crown"></i> Featured</span>
                                @else
                                    <span class="badge bg-light text-muted border">Standard</span>
                                @endif
                            </td>
                            <td class="fs-xs text-muted">
                                {{ $review->created_at?->format('M d, Y') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.reviews.show', $review->id) }}" class="btn btn-sm btn-icon btn-light" title="View Review">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <form action="{{ route('admin.reviews.toggle-featured', $review->id) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-icon btn-light {{ $review->is_featured ? 'text-warning' : 'text-muted' }}" title="Toggle Featured Testimonial">
                                        <i class="ti ti-star"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Review">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No client reviews found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($reviews->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $reviews->links() }}
        </div>
    @endif
</div>
@endsection

