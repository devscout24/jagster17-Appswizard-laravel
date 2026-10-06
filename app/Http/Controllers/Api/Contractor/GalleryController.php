<?php

namespace App\Http\Controllers\Api\Contractor;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GalleryController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $photos = GalleryPhoto::where('business_id', auth('api')->id())
            ->when($request->query('category'), fn ($q, $category) => $q->where('category', $category))
            ->orderBy('sort_order')->latest()
            ->get();

        return $this->success([
            'items' => $photos->map(fn (GalleryPhoto $photo) => $this->present($photo)),
            'categories' => collect(GalleryPhoto::CATEGORIES)->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'usage' => ['used' => $photos->count(), 'limit' => $this->limit()],
        ], 'Gallery retrieved successfully.');
    }

    /**
     * Upload one or more photos (multipart: photos[]).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['image', 'max:10240'],
            'category' => ['nullable', Rule::in(array_keys(GalleryPhoto::CATEGORIES))],
        ]);

        $businessId = auth('api')->id();
        $limit = $this->limit();
        $used = GalleryPhoto::where('business_id', $businessId)->count();

        if ($limit !== null && $used + count($validated['photos']) > $limit) {
            return $this->error("Your plan allows {$limit} gallery images. You have " . max(0, $limit - $used) . ' left.', 422);
        }

        $photos = collect($validated['photos'])->map(fn ($file) => GalleryPhoto::create([
            'business_id' => $businessId,
            'image_path' => $file->store("gallery/{$businessId}", 'public'),
            'caption' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'category' => $validated['category'] ?? 'project_photos',
        ]));

        return $this->success($photos->map(fn ($photo) => $this->present($photo)), 'Photos uploaded.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $photo = $this->find($id);
        $photo->update($request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
            'category' => ['sometimes', Rule::in(array_keys(GalleryPhoto::CATEGORIES))],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]));

        return $this->success($this->present($photo), 'Photo updated.');
    }

    public function destroy(int $id): JsonResponse
    {
        $photo = $this->find($id);

        if (! str_starts_with($photo->image_path, 'http')) {
            Storage::disk('public')->delete($photo->image_path);
        }
        $photo->delete();

        return $this->success(null, 'Photo deleted.');
    }

    private function limit(): ?int
    {
        $plan = auth('api')->user()->subscription?->plan;

        return $plan?->slug === 'elite' ? null : ($plan?->gallery_limit ?? 0);
    }

    private function find(int $id): GalleryPhoto
    {
        return GalleryPhoto::where('business_id', auth('api')->id())->findOrFail($id);
    }

    private function present(GalleryPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'image_url' => $photo->image_url,
            'caption' => $photo->caption,
            'category' => $photo->category,
            'category_label' => GalleryPhoto::CATEGORIES[$photo->category] ?? $photo->category,
            'is_visible' => $photo->status === 'published',
        ];
    }
}
