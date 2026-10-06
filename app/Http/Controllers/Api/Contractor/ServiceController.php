<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $services = Service::with('category')
            ->where('business_id', $user->id)
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->latest()
            ->get();

        return $this->success([
            'items' => $services->map(fn (Service $service) => $this->present($service)),
            'usage' => ['used' => $services->count(), 'limit' => $this->limit()],
        ], 'Services retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $limit = $this->limit();

        if ($limit !== null && Service::where('business_id', auth('api')->id())->count() >= $limit) {
            return $this->error("Your plan allows {$limit} services. Upgrade to add more.", 422);
        }

        $service = Service::create(array_merge($this->validated($request), [
            'business_id' => auth('api')->id(),
            'status' => 'active',
        ]));

        return $this->success($this->present($service->load('category')), 'Service added.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $service = $this->find($id);
        $service->update($this->validated($request, true));

        return $this->success($this->present($service->load('category')), 'Service updated.');
    }

    public function toggle(int $id): JsonResponse
    {
        $service = $this->find($id);
        $service->update(['status' => $service->status === 'active' ? 'inactive' : 'active']);

        return $this->success($this->present($service->load('category')), $service->status === 'active' ? 'Service is now active.' : 'Service turned off.');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->find($id)->delete();

        return $this->success(null, 'Service deleted.');
    }

    /**
     * Service limit from the plan; null means unlimited (Elite).
     */
    private function limit(): ?int
    {
        $plan = auth('api')->user()->subscription?->plan;

        return $plan ? $plan->service_limit : 3;
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'category_id' => [$required, 'integer', Rule::exists('categories', 'id')->where('type', 'service')],
            'description' => ['nullable', 'string', 'max:2000'],
            'pricing_type' => [$required, Rule::in(['fixed', 'hourly', 'sqft', 'custom_quote'])],
            'price' => ['nullable', 'required_unless:pricing_type,custom_quote', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function find(int $id): Service
    {
        return Service::where('business_id', auth('api')->id())->findOrFail($id);
    }

    private function present(Service $service): array
    {
        $price = $service->price !== null ? '$' . number_format((float) $service->price, (float) $service->price == floor((float) $service->price) ? 0 : 2) : null;
        $display = match ($service->pricing_type) {
            'custom_quote' => 'Custom Quote',
            'hourly' => "{$price}/hr",
            'sqft' => "{$price}/sqft",
            default => $service->unit ? "{$price}/{$service->unit}" : $price,
        };

        return [
            'id' => $service->id,
            'name' => $service->name,
            'category' => $service->category ? ['id' => $service->category->id, 'name' => $service->category->name] : null,
            'description' => $service->description,
            'pricing_type' => $service->pricing_type,
            'price' => $service->price !== null ? (float) $service->price : null,
            'unit' => $service->unit,
            'price_display' => $display,
            'is_active' => $service->status === 'active',
        ];
    }
}
