<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'customer_id',
        'project_id',
        'invoice_number',
        'amount',
        'labor_amount',
        'materials_amount',
        'platform_fee',
        'notes',
        'issued_at',
        'due_at',
        'paid_at',
        'status',
        'contractor_customer_id',
        'po_number',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'payment_terms',
        'footer',
        'tags',
        'attachments',
        'require_terms_acceptance',
        'payment_method',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'labor_amount' => 'decimal:2',
            'materials_amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tags' => 'array',
            'attachments' => 'array',
            'require_terms_acceptance' => 'boolean',
            'sent_at' => 'datetime',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function contractorCustomer(): BelongsTo
    {
        return $this->belongsTo(ContractorCustomer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }
}
