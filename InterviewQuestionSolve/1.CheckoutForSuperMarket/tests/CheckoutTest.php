<?php

use PHPUnit\Framework\TestCase;
use App\Checkout;

class CheckoutTest extends TestCase
{
    private Checkout $checkout;
    private array $pricingRules;

    protected function setUp(): void
    {
        $this->checkout = new Checkout();
        $this->pricingRules = $this->checkout->getCurrentPricingRules();
    }

    public function testEmptyCart(): void
    {
        $this->assertEquals(0, $this->checkout->calculatePriceOfCart('', $this->pricingRules));
    }

    public function testSingleItemA(): void
    {
        $this->assertEquals(50, $this->checkout->calculatePriceOfCart('A', $this->pricingRules));
    }

    public function testSingleItemB(): void
    {
        $this->assertEquals(30, $this->checkout->calculatePriceOfCart('B', $this->pricingRules));
    }

    public function testSingleItemC(): void
    {
        $this->assertEquals(20, $this->checkout->calculatePriceOfCart('C', $this->pricingRules));
    }

    public function testSingleItemD(): void
    {
        $this->assertEquals(15, $this->checkout->calculatePriceOfCart('D', $this->pricingRules));
    }

    public function testTwoItemsA(): void
    {
        $this->assertEquals(100, $this->checkout->calculatePriceOfCart('AA', $this->pricingRules));
    }

    public function testThreeItemsAWithPromotion(): void
    {
        $this->assertEquals(130, $this->checkout->calculatePriceOfCart('AAA', $this->pricingRules));
    }

    public function testFourItemsA(): void
    {
        $this->assertEquals(180, $this->checkout->calculatePriceOfCart('AAAA', $this->pricingRules));
    }

    public function testSixItemsA(): void
    {
        $this->assertEquals(260, $this->checkout->calculatePriceOfCart('AAAAAA', $this->pricingRules));
    }

    public function testTwoItemsBWithPromotion(): void
    {
        $this->assertEquals(45, $this->checkout->calculatePriceOfCart('BB', $this->pricingRules));
    }

    public function testThreeItemsB(): void
    {
        $this->assertEquals(75, $this->checkout->calculatePriceOfCart('BBB', $this->pricingRules));
    }

    public function testTwoItemsC(): void
    {
        $this->assertEquals(40, $this->checkout->calculatePriceOfCart('CC', $this->pricingRules));
    }

    public function testThreeItemsCBuyTwoGetOneFree(): void
    {
        $this->assertEquals(40, $this->checkout->calculatePriceOfCart('CCC', $this->pricingRules));
    }

    public function testFourItemsC(): void
    {
        $this->assertEquals(60, $this->checkout->calculatePriceOfCart('CCCC', $this->pricingRules));
    }

    public function testSixItemsC(): void
    {
        $this->assertEquals(80, $this->checkout->calculatePriceOfCart('CCCCCC', $this->pricingRules));
    }

    public function testMixedCartBAB(): void
    {
        $this->assertEquals(95, $this->checkout->calculatePriceOfCart('BAB', $this->pricingRules));
    }

    public function testExampleCart(): void
    {
        $cart = str_repeat('A', 4) . str_repeat('B', 2) . 'C' . 'D';
        $this->assertEquals(260, $this->checkout->calculatePriceOfCart($cart, $this->pricingRules));
    }

    public function testComplexMixedCart(): void
    {
        $cart = str_repeat('A', 6) . str_repeat('B', 5) . str_repeat('C', 3) . str_repeat('D', 2);
        $this->assertEquals(450, $this->checkout->calculatePriceOfCart($cart, $this->pricingRules));
    }

    public function testOrderDoesNotMatter(): void
    {
        $price1 = $this->checkout->calculatePriceOfCart('ABCDABCD', $this->pricingRules);
        $price2 = $this->checkout->calculatePriceOfCart('DDCCBBAA', $this->pricingRules);
        $price3 = $this->checkout->calculatePriceOfCart('AABBCCDD', $this->pricingRules);

        $this->assertEquals($price1, $price2);
        $this->assertEquals($price2, $price3);
    }

    public function testUnknownSKU(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown SKU: Z');
        $this->checkout->calculatePriceOfCart('ABCZ', $this->pricingRules);
    }

    public function testCustomPricingRules(): void
    {
        $customRules = [
            'X' => ['unit' => 100],
            'Y' => [
                'unit' => 200,
                'promotion' => [
                    'quantity' => 2,
                    'price' => 300
                ]
            ]
        ];

        $this->assertEquals(400, $this->checkout->calculatePriceOfCart('XYY', $customRules));
    }
}