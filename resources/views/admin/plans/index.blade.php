@extends('layouts.admin')

@section('title', 'Membership Plans')
@section('page_title', 'Membership & Subscription Tiers')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Membership Plans</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-outline-primary me-2">
        <i class="ti ti-users me-1"></i> Active Subscriptions
    </a>
    <a href="{{ route('admin.plans.create') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus me-1"></i> Create Membership Plan
    </a>
@endsection

@section('content')
<div class="row g-3">
    @forelse($plans as $plan)
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 position-relative">
                @if($plan->is_popular)
                    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-3">MOST POPULAR</span>
                @endif
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-1">{{ $plan->name }}</h5>
                    <p class="text-muted fs-sm mb-3">{{ $plan->tagline ?? 'Verified contractor tier' }}</p>

                    <div class="my-3">
                        <h2 class="fw-bold text-primary mb-0">
                            ${{ number_format($plan->monthly_price, 2) }}
                            <span class="fs-sm text-muted fw-normal">/ month</span>
                        </h2>
                        @if($plan->annual_price)
                            <small class="text-muted">or ${{ number_format($plan->annual_price, 2) }} / year</small>
                        @endif
                    </div>

                    <hr class="my-3" />

                    <h6 class="fw-semibold fs-sm mb-2">Tier Limits &amp; Capabilities:</h6>
                    <ul class="list-unstyled fs-sm mb-3">
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-1"></i>
                            <strong>{{ $plan->service_limit ? $plan->service_limit . ' Services' : 'Unlimited Services' }}</strong>
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-1"></i>
                            <strong>{{ $plan->gallery_limit ? $plan->gallery_limit . ' Gallery Photos' : 'Unlimited Gallery' }}</strong>
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-1"></i>
                            <strong>{{ $plan->video_limit ? $plan->video_limit . ' Portfolio Videos' : 'Video Uploads' }}</strong>
                        </li>
                    </ul>

                    @if(!empty($plan->features))
                        <h6 class="fw-semibold fs-sm mb-2">Included Features:</h6>
                        <ul class="list-unstyled fs-sm mb-4">
                            @foreach((array)$plan->features as $f)
                                <li class="mb-1 text-muted">
                                    <i class="ti ti-circle-check text-primary me-1 fs-xs"></i> {{ $f }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto">
                        <span class="badge bg-light text-dark border">{{ $plan->subscriptions_count }} Active Subscribers</span>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Plan">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                            <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this membership plan?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Plan">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 text-center text-muted">
                <p class="mb-0">No membership plans created yet.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection

