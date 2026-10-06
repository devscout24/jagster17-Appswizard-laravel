<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GalleryPhoto extends Model
{
    public const CATEGORIES = [
        'before_after' => 'Before / After',
        'project_photos' => 'Project Photos',
        'team' => 'Team',
        'equipment' => 'Equipment',
    ];

    protected $fillable = [
        'business_id',
        'image_path',
        'caption',
        'category',
        'status',
        'sort_order',
    ];

    protected $appends = ['image_url'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function getImageUrlAttribute(): string
    {
        return str_starts_with($this->image_path, 'http')
            ? $this->image_path
            : Storage::disk('public')->url($this->image_path);
    }
}
