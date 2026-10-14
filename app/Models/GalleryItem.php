<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryItem extends Model
{
    use HasFactory;

    public const CATEGORIES = ['before_after', 'project_photos', 'team', 'equipment'];

    protected $fillable = [
        'business_id',
        'image',
        'caption',
        'category',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }
}
