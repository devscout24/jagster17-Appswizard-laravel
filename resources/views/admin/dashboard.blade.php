@extends('layouts.admin')

@section('title', 'Admin Dashboard Overview')
@section('page_title', 'Platform Executive Dashboard')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Overview</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.contractors.index') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus me-1"></i> Browse Contractors
    </a>
    <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-file-text me-1"></i> View Leads ({{ $pendingQuotes }})
    </a>
@endsection

@section('content')
<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
    <!-- Total Revenue -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-sm fw-medium text-uppercase">Paid Invoices Volume</span>
                    <span class="avatar-sm rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                        <i class="ti ti-currency-dollar fs-20"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1">${{ number_format($totalRevenue, 2) }}</h3>
                <div class="d-flex align-items-center text-muted fs-xs">
                    <span class="text-success fw-semibold me-1"><i class="ti ti-trending-up"></i> Platform Fee:</span>
                    <span>${{ number_format($totalPlatformFees, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Contractors -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-sm fw-medium text-uppercase">Contractors (Pros)</span>
                    <span class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                        <i class="ti ti-tool fs-20"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format($totalContractors) }}</h3>
                <div class="d-flex align-items-center text-muted fs-xs">
                    <span class="badge bg-primary-subtle text-primary me-1">{{ $activeSubscriptions }} Active Subs</span>
                    <span>Verified network</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers & Clients -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-sm fw-medium text-uppercase">Homeowners & Clients</span>
                    <span class="avatar-sm rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center">
                        <i class="ti ti-users fs-20"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format($totalCustomers) }}</h3>
                <div class="d-flex align-items-center text-muted fs-xs">
                    <span class="text-info fw-semibold me-1">{{ $totalQuotes }}</span>
                    <span>total quotes submitted</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Projects -->
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-sm fw-medium text-uppercase">Active Projects</span>
                    <span class="avatar-sm rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                        <i class="ti ti-hammer fs-20"></i>
                    </span>
                </div>
                <h3 class="fw-bold mb-1">{{ number_format($activeProjects) }}</h3>
                <div class="d-flex align-items-center text-muted fs-xs">
                    <span class="badge bg-warning-subtle text-warning me-1">${{ number_format($pendingInvoiceAmount, 2) }}</span>
                    <span>pending payment</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Approvals & Alerts Banner -->
@if($pendingNominationsCount > 0 || $pendingMessagesCount > 0 || $unverifiedLicensesCount > 0)
<div class="card bg-primary-subtle border-0 mb-4 shadow-sm">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                    <i class="ti ti-bell-ringing fs-18"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-semibold text-primary">Action Items Requiring Administrator Attention</h6>
                    <p class="mb-0 text-muted fs-xs">Pending reviews, verifications, and user inquiries in queue.</p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($pendingNominationsCount > 0)
                    <a href="{{ route('admin.veteran-nominations.index', ['status' => 'pending']) }}" class="btn btn-sm btn-danger shadow-sm">
                        <i class="ti ti-medal me-1"></i> {{ $pendingNominationsCount }} Veteran Grant Requests
                    </a>
                @endif
                @if($pendingMessagesCount > 0)
                    <a href="{{ route('admin.contact-messages.index', ['status' => 'pending']) }}" class="btn btn-sm btn-info text-white shadow-sm">
                        <i class="ti ti-message-2 me-1"></i> {{ $pendingMessagesCount }} Inquiries
                    </a>
                @endif
                @if($unverifiedLicensesCount > 0)
                    <a href="{{ route('admin.contractors.index', ['is_id_verified' => '0']) }}" class="btn btn-sm btn-warning text-dark shadow-sm">
                        <i class="ti ti-id me-1"></i> {{ $unverifiedLicensesCount }} Unverified Contractors
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <!-- Monthly Revenue Area Chart -->
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold">Platform Revenue &amp; Platform Fees (Past 6 Months)</h5>
                <span class="badge bg-success-subtle text-success">Live Financials</span>
            </div>
            <div class="card-body">
                <div id="monthly-revenue-chart" class="apex-charts" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    <!-- Quote Requests Pipeline Donut Chart -->
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold">Quote Pipeline Distribution</h5>
                <a href="{{ route('admin.quotes.index') }}" class="text-primary fs-xs fw-semibold">View All</a>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div id="quote-status-chart" class="apex-charts" style="min-height: 260px;"></div>
                <div class="row g-2 mt-2 pt-2 border-top text-center fs-xs">
                    <div class="col-4">
                        <span class="text-muted d-block">New / Pending</span>
                        <strong class="text-warning fs-sm">{{ $quoteStatuses['new'] + $quoteStatuses['pending'] }}</strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted d-block">Quoted</span>
                        <strong class="text-info fs-sm">{{ $quoteStatuses['quoted'] }}</strong>
                    </div>
                    <div class="col-4">
                        <span class="text-muted d-block">Accepted</span>
                        <strong class="text-success fs-sm">{{ $quoteStatuses['accepted'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Tables Row -->
<div class="row g-3">
    <!-- Recent Quote Requests / Leads -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold">Recent Quote Requests &amp; Leads</h5>
                <a href="{{ route('admin.quotes.index') }}" class="btn btn-xs btn-outline-primary">View All Quotes</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Project &amp; Client</th>
                                <th>Assigned Contractor</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentQuotes as $quote)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.quotes.show', $quote->id) }}" class="fw-semibold text-primary">
                                            {{ $quote->reference_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-truncate" style="max-width: 150px;">{{ $quote->project_title }}</div>
                                        <small class="text-muted">{{ $quote->customer?->name ?? 'Guest Client' }}</small>
                                    </td>
                                    <td>
                                        @if($quote->business)
                                            <span class="fs-xs fw-medium">{{ $quote->business->businessProfile?->business_name ?? $quote->business->name }}</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Open Lead</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($quote->quote_amount)
                                            <span class="fw-bold text-success">${{ number_format($quote->quote_amount, 2) }}</span>
                                        @elseif($quote->budget_min || $quote->budget_max)
                                            <span class="fs-xs text-muted">${{ number_format($quote->budget_min) }} - ${{ number_format($quote->budget_max) }}</span>
                                        @else
                                            <span class="text-muted fs-xs">Flexible</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($quote->status) {
                                                'accepted' => 'bg-success-subtle text-success',
                                                'quoted' => 'bg-info-subtle text-info',
                                                'new', 'pending' => 'bg-warning-subtle text-warning',
                                                'declined', 'rejected', 'expired' => 'bg-danger-subtle text-danger',
                                                default => 'bg-secondary-subtle text-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} text-uppercase">{{ $quote->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.quotes.show', $quote->id) }}" class="btn btn-sm btn-icon btn-light" title="View Details">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No quote requests recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold">Recent Billing Invoices</h5>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-xs btn-outline-primary">View All Invoices</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Contractor</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentInvoices as $inv)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.invoices.show', $inv->id) }}" class="fw-semibold text-primary">
                                            {{ $inv->invoice_number }}
                                        </a>
                                        <div class="fs-xs text-muted">{{ $inv->issued_at?->format('M d, Y') }}</div>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $inv->business?->businessProfile?->business_name ?? $inv->business?->name }}</span>
                                    </td>
                                    <td>
                                        <span>{{ $inv->customer?->name ?? 'Customer' }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold">${{ number_format($inv->amount, 2) }}</span>
                                        @if($inv->platform_fee)
                                            <div class="fs-xs text-muted">Fee: ${{ number_format($inv->platform_fee, 2) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $invBadge = match($inv->status) {
                                                'paid' => 'bg-success-subtle text-success',
                                                'pending' => 'bg-warning-subtle text-warning',
                                                'overdue' => 'bg-danger-subtle text-danger',
                                                'draft' => 'bg-secondary-subtle text-secondary',
                                                default => 'bg-secondary-subtle text-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $invBadge }} text-uppercase">{{ $inv->status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.invoices.show', $inv->id) }}" class="btn btn-sm btn-icon btn-light" title="View Invoice">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No invoices recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Monthly Revenue Chart (ApexCharts)
    var revenueOptions = {
        series: [{
            name: 'Total Revenue ($)',
            data: @json($revenueSeries)
        }, {
            name: 'Platform Fees ($)',
            data: @json($platformFeeSeries)
        }],
        chart: {
            type: 'area',
            height: 320,
            toolbar: { show: false }
        },
        colors: ['#BE1E2D', '#262262'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2 },
        xaxis: {
            categories: @json($months)
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return '$' + value.toLocaleString();
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return '$' + val.toLocaleString();
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [20, 100]
            }
        }
    };
    var revenueChart = new ApexCharts(document.querySelector("#monthly-revenue-chart"), revenueOptions);
    revenueChart.render();

    // 2. Quote Pipeline Status Donut Chart
    var quoteStatusOptions = {
        series: [
            {{ $quoteStatuses['new'] }},
            {{ $quoteStatuses['pending'] }},
            {{ $quoteStatuses['quoted'] }},
            {{ $quoteStatuses['accepted'] }},
            {{ $quoteStatuses['declined'] }},
            {{ $quoteStatuses['rejected'] }}
        ],
        chart: {
            type: 'donut',
            height: 260
        },
        labels: ['New', 'Pending', 'Quoted', 'Accepted', 'Declined', 'Rejected'],
        colors: ['#f7b84b', '#f1963b', '#299cdb', '#0ab39c', '#f06548', '#878a99'],
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: false
        }
    };
    var quoteChart = new ApexCharts(document.querySelector("#quote-status-chart"), quoteStatusOptions);
    quoteChart.render();
});
</script>
@endpush

