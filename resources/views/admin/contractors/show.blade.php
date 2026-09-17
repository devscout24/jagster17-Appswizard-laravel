@extends('layouts.admin')

@section('title', 'Contractor Profile - ' . ($contractor->businessProfile?->business_name ?? $contractor->name))
@section('page_title', $contractor->businessProfile?->business_name ?? $contractor->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.contractors.index') }}">Contractors</a></li>
    <li class="breadcrumb-item active">Details</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.contractors.edit', $contractor->id) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-edit me-1"></i> Edit Profile &amp; Badges
    </a>
    <a href="{{ route('admin.contractors.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Profile Card & Badges -->
    <div class="col-xl-4">
        <!-- Main Card -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center pt-4">
                <img src="{{ asset($contractor->avatar ?? 'assets/images/users/user-1.jpg') }}" class="avatar-xl rounded-circle border p-1 mb-3" alt="Avatar" />
                <h5 class="fw-bold mb-1">{{ $contractor->businessProfile?->business_name ?? $contractor->name }}</h5>
                <p class="text-muted fs-sm mb-2">Owner: {{ $contractor->name }} ({{ $contractor->email }})</p>

                <div class="d-flex justify-content-center gap-1 mb-3">
                    @if($contractor->businessProfile?->is_elite)
                        <span class="badge bg-warning-subtle text-warning"><i class="ti ti-crown"></i> Elite Member</span>
                    @endif
                    @if($contractor->businessProfile?->is_id_verified)
                        <span class="badge bg-success-subtle text-success"><i class="ti ti-shield-check"></i> ID.me Verified</span>
                    @endif
                    @if($contractor->businessProfile?->is_veteran_owned)
                        <span class="badge bg-primary-subtle text-primary"><i class="ti ti-medal"></i> Veteran Owned</span>
                    @endif
                </div>

                <div class="row g-2 border-top border-bottom py-3 my-2 text-center">
                    <div class="col-4">
                        <span class="text-muted fs-xs d-block">Rating</span>
                        <strong class="text-warning fs-base"><i class="ti ti-star-filled"></i> {{ $contractor->businessProfile?->avg_rating ?? '0.0' }}</strong>
                    </div>
                    <div class="col-4 border-start border-end">
                        <span class="text-muted fs-xs d-block">Hourly Rate</span>
                        <strong class="text-dark fs-base">${{ number_format($contractor->businessProfile?->hourly_rate ?? 0, 2) }}</strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted fs-xs d-block">Experience</span>
                        <strong class="text-dark fs-base">{{ $contractor->businessProfile?->years_experience ?? 0 }} Yrs</strong>
                    </div>
                </div>
            </div>

            <div class="card-body border-top pt-3">
                <h6 class="fw-semibold mb-3">Business Information</h6>
                <ul class="list-unstyled mb-0 fs-sm">
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Business Type:</span>
                        <span class="fw-medium">{{ $contractor->businessProfile?->business_type ?? 'N/A' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Tax ID / EIN:</span>
                        <span class="fw-medium">{{ $contractor->businessProfile?->tax_id ?? 'Not Provided' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">State License:</span>
                        <span class="fw-medium text-primary">{{ $contractor->businessProfile?->license_number ?? 'Unlicensed' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Service Radius:</span>
                        <span class="fw-medium">{{ $contractor->businessProfile?->service_radius ?? 25 }} miles</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Phone:</span>
                        <span class="fw-medium">{{ $contractor->phone ?? 'N/A' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Location:</span>
                        <span class="fw-medium">{{ $contractor->businessProfile?->city ?? 'N/A' }}, {{ $contractor->businessProfile?->state ?? '' }} {{ $contractor->businessProfile?->zip_code ?? '' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Hours:</span>
                        <span class="fw-medium">{{ $contractor->businessProfile?->business_hours ?? 'Mon-Fri 8am-5pm' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Subscription Details Card -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0 fw-semibold">Subscription &amp; Membership</h6>
            </div>
            <div class="card-body">
                @if($contractor->subscription)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fs-sm">Current Plan:</span>
                        <span class="badge bg-primary fs-xs text-uppercase">{{ $contractor->subscription->plan?->name ?? 'Active' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fs-sm">Billing Cycle:</span>
                        <span class="fw-medium text-capitalize">{{ $contractor->subscription->billing_cycle }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fs-sm">Renewal Date:</span>
                        <span class="fw-medium">{{ $contractor->subscription->renews_at?->format('M d, Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted fs-sm">Status:</span>
                        <span class="badge bg-success-subtle text-success text-uppercase">{{ $contractor->subscription->status }}</span>
                    </div>
                @else
                    <p class="text-muted fs-sm mb-0">No active subscription plan assigned.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column: Tabs (Services, Products, Quotes, Projects, Invoices, Reviews) -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" data-bs-toggle="tab" href="#tab-services" role="tab">
                            Services ({{ $contractor->services->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#tab-products" role="tab">
                            Marketplace ({{ $contractor->products->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#tab-quotes" role="tab">
                            Quotes ({{ $contractor->quoteRequests->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#tab-projects" role="tab">
                            Projects ({{ $contractor->projects->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#tab-invoices" role="tab">
                            Invoices ({{ $contractor->invoices->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#tab-reviews" role="tab">
                            Reviews ({{ $contractor->reviews->count() }})
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content p-3">
                    <!-- Services Tab -->
                    <div class="tab-pane active" id="tab-services" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th>Category</th>
                                        <th>Pricing</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($contractor->services as $service)
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark">{{ $service->name }}</strong>
                                                <small class="text-muted">{{ Str::limit($service->description, 50) }}</small>
                                            </td>
                                            <td>{{ $service->category?->name ?? 'General' }}</td>
                                            <td>
                                                <span class="fw-bold">${{ number_format($service->price, 2) }}</span>
                                                <small class="text-muted">/ {{ $service->unit ?? $service->pricing_type }}</small>
                                            </td>
                                            <td>
                                                <span class="badge {{ $service->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} text-uppercase">
                                                    {{ $service->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">No services listed yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Products Tab -->
                    <div class="tab-pane" id="tab-products" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($contractor->products as $product)
                                        <tr>
                                            <td class="fw-semibold">{{ $product->name }}</td>
                                            <td><code>{{ $product->sku }}</code></td>
                                            <td class="fw-bold">${{ number_format($product->price, 2) }}</td>
                                            <td>{{ $product->stock_quantity }} in stock</td>
                                            <td>
                                                <span class="badge {{ $product->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                                    {{ $product->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No marketplace products listed yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quotes Tab -->
                    <div class="tab-pane" id="tab-quotes" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Project Title</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($contractor->quoteRequests as $q)
                                        <tr>
                                            <td><a href="{{ route('admin.quotes.show', $q->id) }}" class="fw-semibold text-primary">{{ $q->reference_number }}</a></td>
                                            <td>{{ $q->project_title }}</td>
                                            <td>{{ $q->customer?->name ?? 'Guest Client' }}</td>
                                            <td class="fw-bold text-success">${{ number_format($q->quote_amount ?? 0, 2) }}</td>
                                            <td><span class="badge bg-primary-subtle text-primary text-uppercase">{{ $q->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No quote requests assigned.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Projects Tab -->
                    <div class="tab-pane" id="tab-projects" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project</th>
                                        <th>Client</th>
                                        <th>Progress</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($contractor->projects as $proj)
                                        <tr>
                                            <td><a href="{{ route('admin.projects.show', $proj->id) }}" class="fw-semibold text-primary">{{ $proj->title }}</a></td>
                                            <td>{{ $proj->customer?->name ?? 'Client' }}</td>
                                            <td style="min-width: 120px;">
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-success" style="width: {{ $proj->progress_percent }}%;"></div>
                                                </div>
                                                <small class="text-muted">{{ $proj->progress_percent }}% completed</small>
                                            </td>
                                            <td class="fw-bold">${{ number_format($proj->total_amount ?? 0, 2) }}</td>
                                            <td><span class="badge bg-info-subtle text-info text-uppercase">{{ $proj->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No projects recorded yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Invoices Tab -->
                    <div class="tab-pane" id="tab-invoices" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Platform Fee</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($contractor->invoices as $inv)
                                        <tr>
                                            <td><a href="{{ route('admin.invoices.show', $inv->id) }}" class="fw-semibold text-primary">{{ $inv->invoice_number }}</a></td>
                                            <td>{{ $inv->customer?->name ?? 'Customer' }}</td>
                                            <td class="fw-bold">${{ number_format($inv->amount, 2) }}</td>
                                            <td class="text-muted">${{ number_format($inv->platform_fee ?? 0, 2) }}</td>
                                            <td><span class="badge bg-success-subtle text-success text-uppercase">{{ $inv->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No invoices issued.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Reviews Tab -->
                    <div class="tab-pane" id="tab-reviews" role="tabpanel">
                        @forelse($contractor->reviews as $rev)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div>
                                        <strong class="text-dark">{{ $rev->customer?->name ?? 'Verified Homeowner' }}</strong>
                                        <span class="text-warning ms-2">
                                            @for($s=1; $s<=5; $s++)
                                                <i class="ti ti-star{{ $s <= $rev->rating ? '-filled' : '' }}"></i>
                                            @endfor
                                        </span>
                                    </div>
                                    <small class="text-muted">{{ $rev->created_at?->format('M d, Y') }}</small>
                                </div>
                                <p class="text-muted fs-sm mb-2">{{ $rev->comment }}</p>
                                @if($rev->contractor_reply)
                                    <div class="bg-light p-2 rounded fs-xs text-muted border-start border-3 border-primary ms-3">
                                        <strong>Contractor Response:</strong> {{ $rev->contractor_reply }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted text-center py-3">No client reviews submitted yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

