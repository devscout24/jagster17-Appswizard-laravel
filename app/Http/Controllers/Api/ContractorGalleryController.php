<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryItemResource;
use App\Models\GalleryItem;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\HandlesUploads;
use App\Traits\ResolvesContractorPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContractorGalleryController extends Controller
{
    use ApiResponseTrait, HandlesUploads, ResolvesContractorPlan;

    /**
     * Gallery: photos with category tabs and plan usage (Node 3299-2072).
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $base = GalleryItem::where('business_id', $user->id);

        $query = clone $base;
        if ($request->filled('category') && $request->query('category') !== 'all') {
            $query->where('category', $request->query('category'));
        }

        $counts = (clone $base)->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category');
        $tabs = collect(GalleryItem::CATEGORIES)->map(fn (string $category) => [
            'key'   => $category,
            'label' => $category === 'before_after' ? 'Before / After' : Str::headline($category),
            'count' => (int) ($counts[$category] ?? 0),
        ]);

        return $this->success([
            'usage'      => $this->planUsage($user, 'gallery', (clone $base)->count()),
            'categories' => $tabs,
            'items'      => GalleryItemResource::collection($query->orderBy('sort_order')->latest('id')->get()),
        ], 'Gallery retrieved successfully.');
    }

    /**
     * Add Photos: upload one or more images (multipart: images[], caption, category).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'caption'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', Rule::in(GalleryItem::CATEGORIES)],
        ]);

        $user = auth('api')->user();
        $used = GalleryItem::where('business_id', $user->id)->count();
        $limit = $this->planLimit($user, 'gallery');
        $files = $request->file('images');

        if ($limit !== null && $used + count($files) > $limit) {
            $remaining = max(0, $limit - $used);

            return $this->forbidden($limit === 0
                ? 'Gallery uploads are not included in your plan. Upgrade your membership to add photos.'
                : "Your plan allows up to {$limit} gallery images. You can add {$remaining} more.");
        }

        $nextOrder = (int) GalleryItem::where('business_id', $user->id)->max('sort_order');
        $items = collect($files)->map(fn ($file) => GalleryItem::create([
            'business_id' => $user->id,
            'image'       => $this->storeUpload($file, 'gallery'),
            'caption'     => $request->input('caption'),
            'category'    => $request->input('category', 'project_photos'),
            'sort_order'  => ++$nextOrder,
        ]));

        $this->syncProfileGallery($user);

        return $this->success(GalleryItemResource::collection($items), 'Photos added successfully.', 201);
    }

    /**
     * Edit a photo's caption or category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'caption'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'category'   => ['sometimes', Rule::in(GalleryItem::CATEGORIES)],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $item = GalleryItem::where('business_id', auth('api')->id())->find($id);
        if (! $item) {
            return $this->notFound('Photo not found.');
        }

        $item->update($validated);
        $this->syncProfileGallery(auth('api')->user());

        return $this->success(new GalleryItemResource($item), 'Photo updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $item = GalleryItem::where('business_id', auth('api')->id())->find($id);
        if (! $item) {
            return $this->notFound('Photo not found.');
        }

        $this->deleteUpload($item->image);
        $item->delete();
        $this->syncProfileGallery(auth('api')->user());

        return $this->success(null, 'Photo deleted successfully.');
    }

    /**
     * The public contractor profile reads business_profiles.gallery_images, so mirror the gallery there.
     */
    private function syncProfileGallery(User $user): void
    {
        $urls = GalleryItem::where('business_id', $user->id)
            ->orderBy('sort_order')
            ->pluck('image')
            ->map(fn (string $path) => $this->uploadUrl($path))
            ->all();

        $user->businessProfile?->update(['gallery_images' => $urls]);
    }
}
