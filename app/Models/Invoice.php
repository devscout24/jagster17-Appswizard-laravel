<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'items',
        'bill_to_address',
        'notes',
        'issued_at',
        'due_at',
        'paid_at',
        'payment_method',
        'approved_at',
        'sent_at',
        'status',
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
            'items' => 'array',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Next sequential invoice number (INV-2001, INV-2002, ...).
     */
    public static function nextNumber(): string
    {
        // Longest-then-highest gives numeric order for "INV-<digits>"; older dated
        // numbers (INV-20260914-001) are excluded by the second hyphen.
        $last = static::where('invoice_number', 'like', 'INV-%')
            ->where('invoice_number', 'not like', 'INV-%-%')
            ->orderByRaw('LENGTH(invoice_number) DESC')
            ->orderByDesc('invoice_number')
            ->limit(50)
            ->pluck('invoice_number')
            ->first(fn (string $number) => ctype_digit(substr($number, 4)));

        $next = $last ? ((int) substr($last, 4)) + 1 : 2001;

        return 'INV-' . $next;
    }

    /**
     * Line items with computed amounts. Invoices created before line items existed
     * fall back to a single line built from the labor / materials split or the total.
     */
    public function lineItems(): array
    {
        if (! empty($this->items)) {
            return array_map(fn (array $item) => [
                'name'        => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity'    => (float) $item['quantity'],
                'price'       => (float) $item['price'],
                'amount'      => round((float) $item['quantity'] * (float) $item['price'], 2),
            ], $this->items);
        }

        $fallback = array_filter([
            $this->labor_amount ? ['name' => 'Labor', 'amount' => (float) $this->labor_amount] : null,
            $this->materials_amount ? ['name' => 'Materials', 'amount' => (float) $this->materials_amount] : null,
        ]);

        if (! $fallback) {
            $fallback = [['name' => $this->project?->title ?? 'Services rendered', 'amount' => (float) $this->amount]];
        }

        return array_map(fn (array $item) => [
            'name'        => $item['name'],
            'description' => null,
            'quantity'    => 1.0,
            'price'       => $item['amount'],
            'amount'      => $item['amount'],
        ], array_values($fallback));
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
}
