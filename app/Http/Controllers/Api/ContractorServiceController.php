<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contractor\SaveServiceRequest;
use App\Http\Resources\ContractorServiceResource;
use App\Models\Category;
use App\Models\Service;
use App\Traits\ApiResponseTrait;
use App\Traits\ResolvesContractorPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractorServiceController extends Controller
{
    use ApiResponseTrait, ResolvesContractorPlan;

    /**
     * My Services: service cards, category tabs and plan usage (Node 3123-6885).
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $base = Service::where('business_id', $user->id);

        $query = (clone $base)->with('category');

        if ($request->filled('category_id') && $request->query('category_id') !== 'all') {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }

        $categoryCounts = (clone $base)->selectRaw('category_id, count(*) as total')->groupBy('category_id')->pluck('total', 'category_id');
        $tabs = Category::whereIn('id', $categoryCounts->keys())->orderBy('name')->get()
            ->map(fn (Category $category) => [
                'id'    => $category->id,
                'name'  => $category->name,
                'count' => (int) $categoryCounts[$category->id],
            ])->values();

        return $this->success([
            'usage'      => $this->planUsage($user, 'services', (clone $base)->count()),
            'categories' => $tabs,
            'items'      => ContractorServiceResource::collection($query->latest()->get()),
        ], 'Services retrieved successfully.');
    }

    /**
     * Add Service (enforces the plan's service limit).
     */
    public function store(SaveServiceRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $used = Service::where('business_id', $user->id)->count();
        $limit = $this->planLimit($user, 'services');

        if ($limit !== null && $used >= $limit) {
            return $this->forbidden("Your plan allows up to {$limit} services. Upgrade your membership to add more.");
        }

        $service = Service::create($request->validated() + [
            'business_id' => $user->id,
            'status'      => 'active',
        ]);

        return $this->success(new ContractorServiceResource($service->load('category')), 'Service added successfully.', 201);
    }

    public function show(int $id): JsonResponse
    {
        $service = $this->findOwned($id);

        return $service
            ? $this->success(new ContractorServiceResource($service->load('category')), 'Service retrieved successfully.')
            : $this->notFound('Service not found.');
    }

    public function update(SaveServiceRequest $request, int $id): JsonResponse
    {
        $service = $this->findOwned($id);
        if (! $service) {
            return $this->notFound('Service not found.');
        }

        $validated = $request->validated();
        if (($validated['pricing_type'] ?? $service->pricing_type) === 'custom_quote') {
            $validated['price'] = null;
        }

        $service->update($validated);

        return $this->success(new ContractorServiceResource($service->fresh('category')), 'Service updated successfully.');
    }

    /**
     * Active / Off switch on each service card.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $service = $this->findOwned($id);
        if (! $service) {
            return $this->notFound('Service not found.');
        }

        $service->update(['status' => $service->status === 'active' ? 'inactive' : 'active']);

        return $this->success(
            new ContractorServiceResource($service->load('category')),
            $service->status === 'active' ? 'Service is now active.' : 'Service turned off.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $service = $this->findOwned($id);
        if (! $service) {
            return $this->notFound('Service not found.');
        }

        $service->delete();

        return $this->success(null, 'Service deleted successfully.');
    }

    private function findOwned(int $id): ?Service
    {
        return Service::where('business_id', auth('api')->id())->find($id);
    }
}
