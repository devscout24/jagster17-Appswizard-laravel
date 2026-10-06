<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Models\ContractorCustomer;
use App\Models\Invoice;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customers available in the "Add a customer" dialog: saved contractor customers plus
 * ValorHub customers who already sent this contractor a quote request.
 */
class CustomerController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $businessId = auth('api')->id();
        $search = trim((string) $request->query('search'));

        $saved = ContractorCustomer::where('business_id', $businessId)
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest('updated_at')
            ->get()
            ->map(fn (ContractorCustomer $customer) => $this->present($customer));

        // Platform customers who have worked with this contractor but aren't saved yet
        $platformIds = QuoteRequest::where('business_id', $businessId)->pluck('customer_id')
            ->merge(Invoice::where('business_id', $businessId)->pluck('customer_id'))
            ->filter()->unique();
        $savedUserIds = $saved->pluck('user_id')->filter();

        $platform = User::whereIn('id', $platformIds->diff($savedUserIds))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->get()
            ->map(fn (User $user) => [
                'id' => null,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'billing_address' => null,
                'avatar' => $user->avatar,
                'is_platform_user' => true,
            ]);

        $customers = $saved->concat($platform)->values();

        return $this->success([
            'recent' => $customers->take(3)->values(),
            'customers' => $customers,
        ], 'Customers retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'billing_address' => ['nullable', 'string', 'max:255'],
        ]);

        $businessId = auth('api')->id();
        $customer = ContractorCustomer::updateOrCreate(
            ['business_id' => $businessId, 'email' => strtolower($validated['email'])],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
                // Link to their ValorHub account if they have one, so invoices show in their dashboard
                'user_id' => User::where('email', strtolower($validated['email']))->value('id'),
            ],
        );

        return $this->success($this->present($customer), 'Customer added.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customer = ContractorCustomer::where('business_id', auth('api')->id())->findOrFail($id);
        $customer->update($request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:25'],
            'billing_address' => ['nullable', 'string', 'max:255'],
        ]));

        return $this->success($this->present($customer), 'Customer updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        ContractorCustomer::where('business_id', auth('api')->id())->findOrFail($id)->delete();

        return $this->success(null, 'Customer removed.');
    }

    private function present(ContractorCustomer $customer): array
    {
        return [
            'id' => $customer->id,
            'user_id' => $customer->user_id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'billing_address' => $customer->billing_address,
            'avatar' => $customer->user?->avatar,
            'is_platform_user' => (bool) $customer->user_id,
        ];
    }
}
