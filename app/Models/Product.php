<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'category_id',
        'name',
        'description',
        'image',
        'gallery_images',
        'price',
        'unit',
        'is_elite_tier',
        'is_trending',
        'in_stock',
        'stock_quantity',
        'sku',
        'warranty',
        'shipping_info',
        'external_url',
        'rating',
        'review_count',
        'features',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating' => 'decimal:2',
            'review_count' => 'integer',
            'stock_quantity' => 'integer',
            'is_elite_tier' => 'boolean',
            'is_trending' => 'boolean',
            'in_stock' => 'boolean',
            'gallery_images' => 'array',
            'features' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
