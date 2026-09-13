<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequest\StoreQuoteRequestRequest;
use App\Http\Resources\QuoteRequestResource;
use App\Models\Category;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuoteRequestController extends Controller
{
    use ApiResponseTrait;

    /**
     * Provide wizard configuration, selectable service categories, budgets, and prefill metadata.
     */
    public function config(Request $request): JsonResponse
    {
        $contractorId = $request->query('contractor_id');
        $contractor = null;
        if ($contractorId) {
            $contractor = User::with('businessProfile')->find($contractorId);
        }

        $payload = [
            'steps' => [
                ['step' => 1, 'key' => 'service', 'title' => 'Service', 'subtitle' => 'What service do you need?'],
                ['step' => 2, 'key' => 'description', 'title' => 'Description', 'subtitle' => 'Describe your project'],
                ['step' => 3, 'key' => 'budget_timeline', 'title' => 'Budget & Timeline', 'subtitle' => 'Help pros understand your timing'],
                ['step' => 4, 'key' => 'location', 'title' => 'Location', 'subtitle' => 'Where will the work be performed?'],
                ['step' => 5, 'key' => 'review', 'title' => 'Review', 'subtitle' => 'Review & Submit your request'],
            ],
            'service_options' => $this->getServiceOptions(),
            'budget_options' => [
                ['key' => 'under_1000', 'label' => 'Under $1,000', 'min' => 0, 'max' => 1000],
                ['key' => '1000_5000', 'label' => '$1,000–$5,000', 'min' => 1000, 'max' => 5000],
                ['key' => '5000_15000', 'label' => '$5,000–$15,000', 'min' => 5000, 'max' => 15000],
                ['key' => '15000_plus', 'label' => '$15,000+', 'min' => 15000, 'max' => null],
            ],
            'timeline_options' => [
                ['key' => 'asap', 'label' => 'ASAP', 'description' => 'Immediate / urgent emergency service'],
                ['key' => 'within_1_week', 'label' => 'Within 1 week', 'description' => 'Within the next 7 days'],
                ['key' => 'within_1_month', 'label' => 'Within 1 month', 'description' => 'Standard project scheduling'],
                ['key' => 'flexible', 'label' => 'Flexible', 'description' => 'No rush, flexible schedule'],
            ],
            'target_contractor' => $contractor ? [
                'id' => $contractor->id,
                'business_name' => $contractor->businessProfile?->business_name ?? $contractor->name,
                'avatar' => $contractor->avatar,
                'is_elite' => (bool) ($contractor->businessProfile?->is_elite ?? false),
                'is_veteran_owned' => (bool) ($contractor->businessProfile?->is_veteran_owned ?? false),
                'is_id_verified' => (bool) ($contractor->businessProfile?->is_id_verified ?? false),
            ] : null,
            'user_prefill' => auth('api')->check() ? [
                'name' => auth('api')->user()->name,
                'email' => auth('api')->user()->email,
                'city' => auth('api')->user()->customerProfile?->city,
                'state' => auth('api')->user()->customerProfile?->state,
            ] : null,
        ];

        return $this->success($payload, 'Quote wizard configuration retrieved successfully');
    }

    /**
     * Submit and persist a new quote request from the 5-step wizard.
     */
    public function store(StoreQuoteRequestRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            // Find or create demo/guest customer user
            $user = User::firstOrCreate(
                ['email' => 'guest.customer@valorhub.test'],
                ['name' => 'Homeowner Customer', 'password' => bcrypt(Str::random(16)), 'status' => 'active']
            );
        }

        $businessId = $request->validated('contractor_id') ?? $request->validated('business_id');
        $categoryId = $request->validated('category_id');
        $serviceType = $request->validated('service_type');

        if (!$categoryId && $serviceType) {
            $cat = Category::where('name', 'like', "%{$serviceType}%")->first();
            $categoryId = $cat?->id;
        }

        $budgetRange = $request->validated('budget_range');
        [$minBudget, $maxBudget] = $this->resolveBudgets($budgetRange, $request->validated('budget_min'), $request->validated('budget_max'));

        $referenceNumber = 'VH-'.now()->format('Ymd').'-'.str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

        $quoteRequest = QuoteRequest::create([
            'reference_number' => $referenceNumber,
            'business_id' => $businessId,
            'customer_id' => $user->id,
            'category_id' => $categoryId,
            'project_title' => $request->validated('project_title') ?? ($serviceType ? $serviceType.' Project' : 'Home Service Project'),
            'description' => $request->validated('description'),
            'attachments' => $request->validated('attachments', []),
            'budget_range' => $budgetRange,
            'budget_min' => $minBudget,
            'budget_max' => $maxBudget,
            'timeline' => $request->validated('timeline'),
            'street_address' => $request->validated('street_address'),
            'zip_code' => $request->validated('zip_code'),
            'city' => $request->validated('city'),
            'state' => $request->validated('state'),
            'status' => 'new',
            'requested_at' => now(),
        ]);

        return $this->success(
            new QuoteRequestResource($quoteRequest->load(['business.businessProfile', 'category'])),
            'Your quote request has been submitted successfully!',
            201
        );
    }

    /**
     * List user quote requests.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth('api')->id();
        $status = $request->query('status');

        $query = QuoteRequest::query()
            ->with(['business.businessProfile', 'category', 'customer'])
            ->when($userId, function ($q) use ($userId) {
                $q->where('customer_id', $userId)
                  ->orWhere('business_id', $userId);
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('requested_at');

        $items = $query->paginate(10);

        return $this->success([
            'items' => QuoteRequestResource::collection($items->items()),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ], 'Quote requests retrieved successfully');
    }

    /**
     * View single quote request details.
     */
    public function show(int $id): JsonResponse
    {
        $quoteRequest = QuoteRequest::with(['business.businessProfile', 'category', 'customer.customerProfile'])->find($id);

        if (!$quoteRequest) {
            return $this->notFound('Quote request not found');
        }

        return $this->success(new QuoteRequestResource($quoteRequest), 'Quote request details retrieved successfully');
    }

    /**
     * Private helper to resolve budget boundaries.
     */
    private function resolveBudgets(?string $range, ?float $min, ?float $max): array
    {
        if ($min !== null || $max !== null) {
            return [$min, $max];
        }

        return match ($range) {
            'under_1000' => [0, 1000],
            '1000_5000' => [1000, 5000],
            '5000_15000' => [5000, 15000],
            '15000_plus' => [15000, null],
            default => [null, null],
        };
    }

    /**
     * Private helper for 8 wizard service options.
     */
    private function getServiceOptions(): array
    {
        return [
            ['key' => 'roofing', 'name' => 'Roofing', 'icon' => 'roof-icon', 'category_id' => 1],
            ['key' => 'hvac', 'name' => 'HVAC', 'icon' => 'hvac-icon', 'category_id' => 2],
            ['key' => 'electrical', 'name' => 'Electrical', 'icon' => 'electrical-icon', 'category_id' => 3],
            ['key' => 'landscaping', 'name' => 'Landscaping', 'icon' => 'landscaping-icon', 'category_id' => 4],
            ['key' => 'plumbing', 'name' => 'Plumbing', 'icon' => 'plumbing-icon', 'category_id' => 5],
            ['key' => 'painting', 'name' => 'Painting', 'icon' => 'painting-icon', 'category_id' => 6],
            ['key' => 'contracting', 'name' => 'Contracting', 'icon' => 'contracting-icon', 'category_id' => 7],
            ['key' => 'other', 'name' => 'Other', 'icon' => 'cube-icon', 'category_id' => 8],
        ];
    }
}
