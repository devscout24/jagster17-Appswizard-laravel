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
        'slug',
        'owner_name',
        'business_type',
        'primary_category_id',
        'phone_number',
        'tax_id',
        'address',
        'city',
        'state',
        'zip_code',
        'logo',
        'cover_photo',
        'banner_image',
        'banner_headline',
        'years_experience',
        'member_since',
        'is_elite',
        'is_veteran_owned',
        'is_id_verified',
        'is_available_today',
        'is_license_verified',
        'license_number',
        'license_document',
        'insurance_company',
        'insurance_policy_number',
        'emergency_service',
        'business_hours',
        'service_radius',
        'languages',
        'service_areas',
        'gallery_images',
        'video_urls',
        'hourly_rate',
        'avg_rating',
        'review_count',
        'bio',
        'website_url',
        'custom_domain',
        'linkedin_url',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'onboarding_step',
        'onboarding_completed',
        'terms_accepted_at',
        'id_me_verified_at',
        'id_me_data',
    ];

    protected function casts(): array
    {
        return [
            'years_experience'     => 'integer',
            'member_since'         => 'date',
            'is_elite'             => 'boolean',
            'is_veteran_owned'     => 'boolean',
            'is_id_verified'       => 'boolean',
            'is_available_today'   => 'boolean',
            'is_license_verified'  => 'boolean',
            'emergency_service'    => 'boolean',
            'service_radius'       => 'integer',
            'languages'            => 'array',
            'service_areas'        => 'array',
            'gallery_images'       => 'array',
            'video_urls'           => 'array',
            'hourly_rate'          => 'decimal:2',
            'avg_rating'           => 'decimal:2',
            'review_count'         => 'integer',
            'onboarding_step'      => 'integer',
            'onboarding_completed' => 'boolean',
            'terms_accepted_at'    => 'datetime',
            'id_me_verified_at'    => 'datetime',
            'id_me_data'           => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }
}
