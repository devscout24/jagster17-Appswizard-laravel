<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'quote_request_id',
        'title',
        'description',
        'total_amount',
        'start_date',
        'due_date',
        'progress_percent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'     => 'decimal:2',
            'start_date'       => 'date',
            'due_date'         => 'date',
            'progress_percent' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_request_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'project_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'project_id');
    }
}
