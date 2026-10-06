<?php

namespace App\Services;

/**
 * Line-item and discount maths shared by invoice create/update/duplicate.
 */
class InvoiceTotals
{
    /**
     * @param  array<int, array{description: string, quantity: float|int|string, unit_price: float|int|string}>  $items
     * @return array{items: array<int, array<string, mixed>>, subtotal: float, discount_amount: float, total: float}
     */
    public static function calculate(array $items, float $discountPercent = 0): array
    {
        $lines = [];
        $subtotal = 0.0;

        foreach (array_values($items) as $index => $item) {
            $quantity = max(0, (float) $item['quantity']);
            $unitPrice = max(0, (float) $item['unit_price']);
            $amount = round($quantity * $unitPrice, 2);
            $subtotal += $amount;

            $lines[] = [
                'description' => $item['description'],
                'details' => $item['details'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'sort_order' => $index,
            ];
        }

        $discountPercent = min(100, max(0, $discountPercent));
        $discountAmount = round($subtotal * $discountPercent / 100, 2);

        return [
            'items' => $lines,
            'subtotal' => round($subtotal, 2),
            'discount_amount' => $discountAmount,
            'total' => round($subtotal - $discountAmount, 2),
        ];
    }
}
