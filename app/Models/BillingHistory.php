<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingHistory extends Model
{
    use HasFactory;

    protected $table = 'billing_history';

    protected $fillable = [
        'subscription_id',
        'plan_name',
        'amount',
        'status',
        'receipt_url',
        'billed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'billed_at' => 'date',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }
}
