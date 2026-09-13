<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array matching the Figma Product Details page.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $seller = $this->business;
        $profile = $seller?->businessProfile;
        $location = array_filter([$profile?->city, $profile?->state]);

        $rating = (float) ($this->rating > 0 ? $this->rating : ($profile?->avg_rating ?? 4.9));
        $reviewCount = (int) ($this->review_count > 0 ? $this->review_count : ($profile?->review_count ?? 127));

        $gallery = $this->gallery_images ?? [];
        if (empty($gallery) && $this->image) {
            $gallery = [$this->image];
        }

        // Query related products from same category or seller
        $relatedProducts = Product::query()
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('category_id', $this->category_id)
                  ->orWhere('business_id', $this->business_id);
            })
            ->with(['business.businessProfile', 'category'])
            ->take(4)
            ->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description ?? 'Professional-grade equipment and supplies backed by verified contractor quality.',
            'price' => (float) $this->price,
            'price_display' => '$'.number_format((float) $this->price, 2),
            'unit' => $this->unit,
            'is_elite_tier' => (bool) $this->is_elite_tier,
            'is_trending' => (bool) $this->is_trending,
            'rating' => $rating,
            'review_count' => $reviewCount,
            'rating_display' => '★ '.number_format($rating, 1).' ('.$reviewCount.' reviews)',
            'image' => $this->image ?? 'https://images.test/product-main.jpg',
            'gallery_images' => $gallery,
            'in_stock' => (bool) $this->in_stock,
            'stock_quantity' => (int) $this->stock_quantity,
            'sku' => $this->sku ?? 'VH-PRD-'.str_pad($this->id, 5, '0', STR_PAD_LEFT),
            'warranty' => $this->warranty ?? '1 year manufacturer warranty',
            'shipping_info' => $this->shipping_info ?? 'Ships nationwide · 2-3 business days',
            'external_url' => $this->external_url ?? $profile?->website_url ?? 'https://seller.test',
            'redirection_notice' => "You will be redirected to the seller's own website to complete your purchase.",
            'features' => $this->features ?? [],
            'specifications' => [
                ['label' => 'Category', 'value' => $this->category?->name ?? 'Plumbing'],
                ['label' => 'Availability', 'value' => $this->in_stock ? 'In stock · ships in 2 days' : 'Out of stock'],
                ['label' => 'Warranty', 'value' => $this->warranty ?? '1 year manufacturer warranty'],
                ['label' => 'Shipping', 'value' => $this->shipping_info ?? 'Ships nationwide · 2-3 business days'],
            ],
            'seller' => [
                'id' => $seller?->id,
                'business_name' => $profile?->business_name ?? $seller?->name,
                'avatar' => $seller?->avatar,
                'rating' => (float) ($profile?->avg_rating ?? 4.9),
                'review_count' => (int) ($profile?->review_count ?? 120),
                'is_elite' => (bool) ($profile?->is_elite ?? false),
                'is_id_verified' => (bool) ($profile?->is_id_verified ?? false),
                'is_veteran_owned' => (bool) ($profile?->is_veteran_owned ?? false),
                'location' => !empty($location) ? implode(', ', $location) : 'Nationwide',
                'website_url' => $profile?->website_url,
                'profile_url' => '/contractors/'.$seller?->id,
            ],
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name ?? 'Plumbing Supplies',
            ],
            'related_products' => ProductCardResource::collection($relatedProducts),
            'reviews' => $seller?->reviews ? $seller->reviews->take(4)->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'author_name' => $review->customer?->name ?? 'Verified Buyer',
                    'author_avatar' => $review->customer?->avatar,
                    'created_at' => $review->created_at?->format('M d, Y'),
                ];
            }) : [],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Marketplace', 'url' => '/marketplace'],
                ['label' => 'Browse Products', 'url' => '/marketplace/products'],
                ['label' => $this->name, 'url' => null],
            ],
        ];
    }
}
