<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'business_id',
        'customer_id',
        'category_id',
        'project_title',
        'description',
        'attachments',
        'budget_range',
        'budget_min',
        'budget_max',
        'timeline',
        'street_address',
        'zip_code',
        'city',
        'state',
        'quote_amount',
        'labor_cost',
        'materials_cost',
        'tax_cost',
        'estimated_duration',
        'contractor_notes',
        'status',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'quote_amount' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'materials_cost' => 'decimal:2',
            'tax_cost' => 'decimal:2',
            'requested_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteRequest $quote) {
            if (empty($quote->reference_number)) {
                $quote->reference_number = 'VH-'.now()->format('Ymd').'-'.str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'quote_request_id');
    }
}
