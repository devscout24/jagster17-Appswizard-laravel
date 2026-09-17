@extends('layouts.admin')

@section('title', 'Customer Profile - ' . $customer->name)
@section('page_title', $customer->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item active">Profile</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-edit me-1"></i> Edit Profile
    </a>
    <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i> Back to List
    </a>
@endsection

@section('content')
<div class="row g-3">
    <!-- Left Column: Customer Profile Card -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center pt-4">
                <img src="{{ asset($customer->avatar ?? 'assets/images/users/user-5.jpg') }}" class="avatar-xl rounded-circle border p-1 mb-3" alt="Avatar" />
                <h5 class="fw-bold mb-1">{{ $customer->name }}</h5>
                <p class="text-muted fs-sm mb-3">{{ $customer->email }}</p>

                <div class="row g-2 border-top border-bottom py-3 text-center">
                    <div class="col-6 border-end">
                        <span class="text-muted fs-xs d-block">Total Spent</span>
                        <strong class="text-success fs-base">${{ number_format($totalSpent, 2) }}</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted fs-xs d-block">Account Status</span>
                        <span class="badge {{ $customer->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                            {{ $customer->status ?? 'active' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body border-top pt-3">
                <h6 class="fw-semibold mb-3">Contact &amp; Location Details</h6>
                <ul class="list-unstyled mb-0 fs-sm">
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Phone:</span>
                        <span class="fw-medium">{{ $customer->phone ?? 'N/A' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">City:</span>
                        <span class="fw-medium">{{ $customer->customerProfile?->city ?? 'N/A' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">State:</span>
                        <span class="fw-medium">{{ $customer->customerProfile?->state ?? 'N/A' }}</span>
                    </li>
                    <li class="mb-2 d-flex justify-content-between">
                        <span class="text-muted">Registered:</span>
                        <span class="fw-medium">{{ $customer->created_at?->format('M d, Y') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Activity Tabs (Quotes, Projects, Invoices, Payments) -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" data-bs-toggle="tab" href="#cust-quotes" role="tab">
                            Quote Requests ({{ $customer->customerQuoteRequests->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#cust-projects" role="tab">
                            Projects ({{ $customer->customerProjects->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#cust-invoices" role="tab">
                            Invoices ({{ $customer->customerInvoices->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#cust-payments" role="tab">
                            Payments ({{ $customer->customerPayments->count() }})
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="tab-content p-3">
                    <!-- Quotes Tab -->
                    <div class="tab-pane active" id="cust-quotes" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Project Title</th>
                                        <th>Contractor</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->customerQuoteRequests as $q)
                                        <tr>
                                            <td><a href="{{ route('admin.quotes.show', $q->id) }}" class="fw-semibold text-primary">{{ $q->reference_number }}</a></td>
                                            <td>{{ $q->project_title }}</td>
                                            <td>{{ $q->business?->businessProfile?->business_name ?? 'Open Request' }}</td>
                                            <td class="fw-bold text-success">${{ number_format($q->quote_amount ?? 0, 2) }}</td>
                                            <td><span class="badge bg-primary-subtle text-primary text-uppercase">{{ $q->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No quote requests submitted.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Projects Tab -->
                    <div class="tab-pane" id="cust-projects" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project</th>
                                        <th>Contractor</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->customerProjects as $p)
                                        <tr>
                                            <td><a href="{{ route('admin.projects.show', $p->id) }}" class="fw-semibold text-primary">{{ $p->title }}</a></td>
                                            <td>{{ $p->business?->businessProfile?->business_name ?? $p->business?->name }}</td>
                                            <td style="min-width: 100px;">
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-success" style="width: {{ $p->progress_percent }}%;"></div>
                                                </div>
                                                <small class="text-muted">{{ $p->progress_percent }}%</small>
                                            </td>
                                            <td><span class="badge bg-info-subtle text-info text-uppercase">{{ $p->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">No projects ongoing.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Invoices Tab -->
                    <div class="tab-pane" id="cust-invoices" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Contractor</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->customerInvoices as $inv)
                                        <tr>
                                            <td><a href="{{ route('admin.invoices.show', $inv->id) }}" class="fw-semibold text-primary">{{ $inv->invoice_number }}</a></td>
                                            <td>{{ $inv->business?->businessProfile?->business_name ?? $inv->business?->name }}</td>
                                            <td class="fw-bold">${{ number_format($inv->amount, 2) }}</td>
                                            <td><span class="badge bg-success-subtle text-success text-uppercase">{{ $inv->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">No invoices billed.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Tab -->
                    <div class="tab-pane" id="cust-payments" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customer->customerPayments as $pay)
                                        <tr>
                                            <td><code>{{ $pay->transaction_id }}</code></td>
                                            <td>{{ $pay->payment_method }}</td>
                                            <td class="fw-bold text-success">${{ number_format($pay->amount, 2) }}</td>
                                            <td class="text-muted fs-xs">{{ $pay->paid_at?->format('M d, Y H:i') }}</td>
                                            <td><span class="badge bg-success-subtle text-success text-uppercase">{{ $pay->status }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">No payments recorded.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

