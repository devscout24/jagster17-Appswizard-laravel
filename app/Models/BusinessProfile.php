<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'city',
        'state',
        'zip_code',
        'cover_photo',
        'years_experience',
        'member_since',
        'is_elite',
        'is_veteran_owned',
        'is_id_verified',
        'is_available_today',
        'is_license_verified',
        'license_number',
        'business_hours',
        'languages',
        'service_areas',
        'gallery_images',
        'video_urls',
        'hourly_rate',
        'avg_rating',
        'review_count',
        'bio',
        'website_url',
    ];

    protected function casts(): array
    {
        return [
            'years_experience' => 'integer',
            'member_since' => 'date',
            'is_elite' => 'boolean',
            'is_veteran_owned' => 'boolean',
            'is_id_verified' => 'boolean',
            'is_available_today' => 'boolean',
            'is_license_verified' => 'boolean',
            'languages' => 'array',
            'service_areas' => 'array',
            'gallery_images' => 'array',
            'video_urls' => 'array',
            'hourly_rate' => 'decimal:2',
            'avg_rating' => 'decimal:2',
            'review_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
