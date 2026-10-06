@extends('layouts.admin')

@section('title', 'Homepage Banners')
@section('page_title', 'Homepage Banners (Elite)')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Homepage Banners</li>
@endsection

@section('content')
<p class="text-muted mb-3">Elite contractors submit a banner for the homepage rotation. Approved banners go live immediately.</p>

<ul class="nav nav-pills gap-2 mb-3">
    @foreach (['pending' => 'Pending Review', 'approved' => 'Live', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
        <li class="nav-item">
            <a href="{{ route('admin.banners.index', ['status' => $value]) }}" class="nav-link {{ $status === $value ? 'active' : 'bg-white border' }}">
                {{ $label }}
                @if ($value !== 'all')
                    <span class="badge {{ $status === $value ? 'bg-white text-primary' : 'bg-light text-dark' }} ms-1">{{ $counts[$value] ?? 0 }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>

<div class="row g-3">
    @forelse ($banners as $banner)
        @php
            $badge = match ($banner->status) {
                'approved' => ['bg-success', 'Live'],
                'rejected' => ['bg-danger', 'Rejected'],
                default => ['bg-warning', 'Pending'],
            };
        @endphp
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="position-relative">
                    <img src="{{ $banner->image_url }}" alt="" class="card-img-top object-fit-cover" style="height: 160px;" />
                    <span class="badge {{ $badge[0] }} position-absolute top-0 end-0 m-2">{{ $badge[1] }}</span>
                    <div class="position-absolute bottom-0 start-0 end-0 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,.75));">
                        <div class="text-white fw-bold">{{ $banner->headline ?? ($banner->business?->businessProfile?->business_name ?? '') }}</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <a href="{{ route('admin.contractors.show', $banner->business_id) }}" class="fw-bold text-primary">
                                {{ $banner->business?->businessProfile?->business_name ?? $banner->business?->name }}
                            </a>
                            <div class="fs-xs text-muted">Submitted {{ $banner->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Delete this banner?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete"><i class="ti ti-trash"></i></button>
                        </form>
                    </div>

                    @if ($banner->rejection_reason)
                        <div class="alert alert-danger py-2 fs-xs mb-2">{{ $banner->rejection_reason }}</div>
                    @endif

                    <form action="{{ route('admin.banners.update', $banner) }}" method="POST" class="d-flex gap-2">
                        @csrf
                        @method('PUT')
                        <input type="text" name="rejection_reason" class="form-control form-control-sm" placeholder="Reason (required to reject)" />
                        @if ($banner->status !== 'approved')
                            <button type="submit" name="decision" value="approved" class="btn btn-sm btn-success text-nowrap"><i class="ti ti-check"></i> Approve</button>
                        @endif
                        @if ($banner->status !== 'rejected')
                            <button type="submit" name="decision" value="rejected" class="btn btn-sm btn-outline-danger text-nowrap"><i class="ti ti-x"></i> Reject</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-muted">
                <i class="ti ti-photo fs-2 d-block mb-2"></i>No banners in this list.
            </div></div>
        </div>
    @endforelse
</div>

@if ($banners->hasPages())
    <div class="mt-3">{{ $banners->links() }}</div>
@endif
@endsection
