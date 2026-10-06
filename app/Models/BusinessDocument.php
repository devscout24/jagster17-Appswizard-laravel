<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BusinessDocument extends Model
{
    public const TYPES = [
        'license_insurance' => 'License & Insurance',
        'logo' => 'Business Logo',
        'cover_photo' => 'Cover Photo',
        'other' => 'Other',
    ];

    protected $fillable = [
        'business_id',
        'type',
        'file_path',
        'original_name',
        'status',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $appends = ['file_url'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getFileUrlAttribute(): string
    {
        return str_starts_with($this->file_path, 'http')
            ? $this->file_path
            : Storage::disk('public')->url($this->file_path);
    }
}
