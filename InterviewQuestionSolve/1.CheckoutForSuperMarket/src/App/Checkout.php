<?php

namespace App;



class Checkout
{
    public function calculatePriceOfCart(string $cart, array $pricingRules): int
    {
        $items = $this->countItems($cart);
        $total = 0;

        foreach ($items as $sku => $quantity) {
            if (!isset($pricingRules[$sku])) {
                throw new \InvalidArgumentException("Unknown SKU: {$sku}");
            }

            $total += $this->calculateItemPrice($sku, $quantity, $pricingRules[$sku]);
        }

        return $total;
    }

    private function countItems(string $cart): array
    {
        $items = [];
        $length = strlen($cart);

        for ($i = 0; $i < $length; $i++) {
            $sku = $cart[$i];
            if (!isset($items[$sku])) {
                $items[$sku] = 0;
            }
            $items[$sku]++;
        }

        return $items;
    }

    private function calculateItemPrice(string $sku, int $quantity, array $rule): int
    {
        $unitPrice = $rule['unit'];

        if (!isset($rule['promotion'])) {
            return $quantity * $unitPrice;
        }

        $promotion = $rule['promotion'];

        if (isset($promotion['type']) && $promotion['type'] === 'buy_x_get_y_free') {
            return $this->calculateBuyXGetYFree($quantity, $unitPrice, $promotion);
        }

        if (isset($promotion['quantity']) && isset($promotion['price'])) {
            return $this->calculateBulkDiscount($quantity, $unitPrice, $promotion);
        }

        return $quantity * $unitPrice;
    }

    private function calculateBuyXGetYFree(int $quantity, int $unitPrice, array $promotion): int
    {
        $buy = $promotion['buy'];
        $get = $promotion['get'];
        $setSize = $buy + $get;

        $completeSets = (int) ($quantity / $setSize);
        $remainder = $quantity % $setSize;

        return ($completeSets * $buy * $unitPrice) + ($remainder * $unitPrice);
    }

    private function calculateBulkDiscount(int $quantity, int $unitPrice, array $promotion): int
    {
        $promoQuantity = $promotion['quantity'];
        $promoPrice = $promotion['price'];

        $promoSets = (int) ($quantity / $promoQuantity);
        $remainder = $quantity % $promoQuantity;

        return ($promoSets * $promoPrice) + ($remainder * $unitPrice);
    }


    /**
     * Pricing rule structure:
     * [
     *   'A' => ['unit' => 50, 'promotion' => ['quantity' => 3, 'price' => 130]],
     *   'B' => ['unit' => 30, 'promotion' => ['quantity' => 2, 'price' => 45]],
     *   'C' => ['unit' => 20, 'promotion' => ['type' => 'buy_x_get_y_free', 'buy' => 2, 'get' => 1]],
     *   'D' => ['unit' => 15]
     * ]
     */

    public function getCurrentPricingRules(): array
    {
        return [
            'A' => [
                'unit' => 50,
                'promotion' => [
                    'quantity' => 3,
                    'price' => 130
                ]
            ],
            'B' => [
                'unit' => 30,
                'promotion' => [
                    'quantity' => 2,
                    'price' => 45
                ]
            ],
            'C' => [
                'unit' => 20,
                'promotion' => [
                    'type' => 'buy_x_get_y_free',
                    'buy' => 2,
                    'get' => 1
                ]
            ],
            'D' => [
                'unit' => 15
            ]
        ];
    }
}