<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\ContactMessage;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VeteranNomination;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main Admin Dashboard overview.
     */
    public function index(Request $request): View
    {
        // 1. KPI Counts
        $totalContractors = User::whereHas('roles', fn($q) => $q->where('name', 'business'))->count();
        $totalCustomers = User::whereHas('roles', fn($q) => $q->where('name', 'customer'))->count();
        $totalProducts = Product::count();
        $activeProjects = Project::whereIn('status', ['in_progress', 'scheduled'])->count();
        $totalQuotes = QuoteRequest::count();
        $pendingQuotes = QuoteRequest::whereIn('status', ['new', 'pending'])->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();

        // 2. Financial Metrics
        $totalRevenue = (float) Invoice::where('status', 'paid')->sum('amount');
        $totalPlatformFees = (float) Invoice::where('status', 'paid')->sum('platform_fee');
        $pendingInvoiceAmount = (float) Invoice::whereIn('status', ['pending', 'overdue'])->sum('amount');

        // 3. Monthly Revenue (Past 6 Months) for Area/Bar Chart
        $months = [];
        $revenueSeries = [];
        $platformFeeSeries = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthLabel = $monthDate->format('M Y');
            $months[] = $monthLabel;

            $rev = (float) Invoice::where('status', 'paid')
                ->whereYear('paid_at', $monthDate->year)
                ->whereMonth('paid_at', $monthDate->month)
                ->sum('amount');

            $fee = (float) Invoice::where('status', 'paid')
                ->whereYear('paid_at', $monthDate->year)
                ->whereMonth('paid_at', $monthDate->month)
                ->sum('platform_fee');

            $revenueSeries[] = round($rev, 2);
            $platformFeeSeries[] = round($fee, 2);
        }

        // 4. Quote Request Status Breakdown for Donut Chart
        $quoteStatuses = [
            'new' => QuoteRequest::where('status', 'new')->count(),
            'pending' => QuoteRequest::where('status', 'pending')->count(),
            'quoted' => QuoteRequest::where('status', 'quoted')->count(),
            'accepted' => QuoteRequest::where('status', 'accepted')->count(),
            'declined' => QuoteRequest::where('status', 'declined')->count(),
            'rejected' => QuoteRequest::where('status', 'rejected')->count(),
        ];

        // 5. Recent Activity
        $recentContractors = User::whereHas('roles', fn($q) => $q->where('name', 'business'))
            ->with(['businessProfile', 'subscription.plan'])
            ->latest()
            ->take(5)
            ->get();

        $recentQuotes = QuoteRequest::with(['business.businessProfile', 'customer', 'category'])
            ->latest('requested_at')
            ->take(5)
            ->get();

        $recentInvoices = Invoice::with(['business.businessProfile', 'customer', 'project'])
            ->latest('issued_at')
            ->take(5)
            ->get();

        // 6. Action Items / Pending Approvals
        $pendingNominationsCount = VeteranNomination::where('status', 'pending')->count();
        $pendingMessagesCount = ContactMessage::where('status', 'pending')->count();
        $unverifiedLicensesCount = BusinessProfile::where('is_license_verified', false)->count();

        return view('admin.dashboard', compact(
            'totalContractors',
            'totalCustomers',
            'totalProducts',
            'activeProjects',
            'totalQuotes',
            'pendingQuotes',
            'activeSubscriptions',
            'totalRevenue',
            'totalPlatformFees',
            'pendingInvoiceAmount',
            'months',
            'revenueSeries',
            'platformFeeSeries',
            'quoteStatuses',
            'recentContractors',
            'recentQuotes',
            'recentInvoices',
            'pendingNominationsCount',
            'pendingMessagesCount',
            'unverifiedLicensesCount'
        ));
    }
}
