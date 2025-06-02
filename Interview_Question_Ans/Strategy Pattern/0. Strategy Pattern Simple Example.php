<?php

/*
===============================================================================
STRATEGY PATTERN - সহজ ও সংক্ষিপ্ত গাইড
===============================================================================

STRATEGY PATTERN কি?
--------------------
একই কাজ করার বিভিন্ন উপায় (algorithms) আলাদা আলাদা class এ রেখে 
প্রয়োজন অনুযায়ী ব্যবহার করার pattern।

সহজ ভাষায়: "একই কাজ, বিভিন্ন পদ্ধতি"

উদাহরণ:
- Payment: Credit Card / PayPal / Bank Transfer
- Shipping: Express / Standard / Economy  
- Sorting: Quick Sort / Bubble Sort / Merge Sort

কেন ব্যবহার করব?
------------------
✅ Runtime এ method change করা যায়
✅ নতুন method সহজে add করা যায়
✅ if/else এর পরিবর্তে clean code
✅ Testing সহজ হয়
*/

// ===============================================================================
// 1. BASIC STRATEGY PATTERN - PAYMENT EXAMPLE
// ===============================================================================

// Strategy Interface - সব payment method এর common contract
interface PaymentStrategy {
    public function pay(float $amount): string;
    public function getFee(float $amount): float;
}

// Concrete Strategy 1 - Credit Card
class CreditCardPayment implements PaymentStrategy {
    private $cardNumber;
    
    public function __construct(string $cardNumber) {
        $this->cardNumber = $cardNumber;
    }
    
    public function pay(float $amount): string {
        $fee = $this->getFee($amount);
        $total = $amount + $fee;
        
        return "💳 Credit Card Payment: $" . number_format($total, 2) . 
               " (Card: ****" . substr($this->cardNumber, -4) . ")";
    }
    
    public function getFee(float $amount): float {
        return $amount * 0.03; // 3% fee
    }
}

// Concrete Strategy 2 - PayPal
class PayPalPayment implements PaymentStrategy {
    private $email;
    
    public function __construct(string $email) {
        $this->email = $email;
    }
    
    public function pay(float $amount): string {
        $fee = $this->getFee($amount);
        $total = $amount + $fee;
        
        return "🅿️ PayPal Payment: $" . number_format($total, 2) . 
               " (Account: {$this->email})";
    }
    
    public function getFee(float $amount): float {
        return $amount * 0.034 + 0.30; // 3.4% + $0.30
    }
}

// Concrete Strategy 3 - Bank Transfer
class BankTransferPayment implements PaymentStrategy {
    private $accountNumber;
    
    public function __construct(string $accountNumber) {
        $this->accountNumber = $accountNumber;
    }
    
    public function pay(float $amount): string {
        $fee = $this->getFee($amount);
        $total = $amount + $fee;
        
        return "🏦 Bank Transfer: $" . number_format($total, 2) . 
               " (Account: ****" . substr($this->accountNumber, -4) . ")";
    }
    
    public function getFee(float $amount): float {
        return 1.50; // Fixed $1.50 fee
    }
}

// Context Class - Strategy ব্যবহার করে
class PaymentProcessor {
    private $strategy;
    
    // Strategy set করা
    public function setPaymentMethod(PaymentStrategy $strategy): void {
        $this->strategy = $strategy;
    }
    
    // Payment process করা
    public function processPayment(float $amount): string {
        if (!$this->strategy) {
            return "❌ No payment method selected!";
        }
        
        return $this->strategy->pay($amount);
    }
    
    // Fee calculate করা
    public function calculateFee(float $amount): float {
        if (!$this->strategy) {
            return 0;
        }
        
        return $this->strategy->getFee($amount);
    }
}

// ===============================================================================
// 2. SIMPLE USAGE EXAMPLE
// ===============================================================================

echo "💰 STRATEGY PATTERN - PAYMENT EXAMPLE\n";
echo str_repeat("=", 50) . "\n\n";

// Payment processor তৈরি করা
$processor = new PaymentProcessor();
$amount = 100.00;

// Different payment methods test করা
$paymentMethods = [
    new CreditCardPayment('4532123456789012'),
    new PayPalPayment('user@example.com'),
    new BankTransferPayment('1234567890')
];

foreach ($paymentMethods as $method) {
    $processor->setPaymentMethod($method);
    
    echo "Method: " . get_class($method) . "\n";
    echo "Fee: $" . number_format($processor->calculateFee($amount), 2) . "\n";
    echo "Result: " . $processor->processPayment($amount) . "\n\n";
}

// ===============================================================================
// 3. SHIPPING STRATEGY EXAMPLE
// ===============================================================================

// Shipping Strategy Interface
interface ShippingStrategy {
    public function calculateCost(float $weight): float;
    public function getDeliveryTime(): string;
}

// Express Shipping
class ExpressShipping implements ShippingStrategy {
    public function calculateCost(float $weight): float {
        return 15.00 + ($weight * 2.50); // Base $15 + $2.50 per lb
    }
    
    public function getDeliveryTime(): string {
        return "1-2 business days";
    }
}

// Standard Shipping
class StandardShipping implements ShippingStrategy {
    public function calculateCost(float $weight): float {
        return 8.00 + ($weight * 1.20); // Base $8 + $1.20 per lb
    }
    
    public function getDeliveryTime(): string {
        return "3-5 business days";
    }
}

// Economy Shipping
class EconomyShipping implements ShippingStrategy {
    public function calculateCost(float $weight): float {
        return 5.00 + ($weight * 0.80); // Base $5 + $0.80 per lb
    }
    
    public function getDeliveryTime(): string {
        return "7-10 business days";
    }
}

// Shipping Calculator
class ShippingCalculator {
    private $strategy;
    
    public function setShippingMethod(ShippingStrategy $strategy): void {
        $this->strategy = $strategy;
    }
    
    public function calculate(float $weight): array {
        if (!$this->strategy) {
            return ['error' => 'No shipping method selected'];
        }
        
        return [
            'cost' => $this->strategy->calculateCost($weight),
            'delivery_time' => $this->strategy->getDeliveryTime(),
            'method' => get_class($this->strategy)
        ];
    }
}

// ===============================================================================
// 4. SHIPPING EXAMPLE USAGE
// ===============================================================================

echo "📦 STRATEGY PATTERN - SHIPPING EXAMPLE\n";
echo str_repeat("=", 50) . "\n\n";

$calculator = new ShippingCalculator();
$weight = 3.5; // pounds

$shippingMethods = [
    'Express' => new ExpressShipping(),
    'Standard' => new StandardShipping(),
    'Economy' => new EconomyShipping()
];

foreach ($shippingMethods as $name => $method) {
    $calculator->setShippingMethod($method);
    $result = $calculator->calculate($weight);
    
    echo "$name Shipping:\n";
    echo "  Cost: $" . number_format($result['cost'], 2) . "\n";
    echo "  Delivery: {$result['delivery_time']}\n\n";
}

// ===============================================================================
// 5. SIMPLE FACTORY FOR STRATEGIES
// ===============================================================================

class PaymentFactory {
    public static function create(string $type, array $config): PaymentStrategy {
        switch (strtolower($type)) {
            case 'credit_card':
                return new CreditCardPayment($config['card_number']);
            case 'paypal':
                return new PayPalPayment($config['email']);
            case 'bank_transfer':
                return new BankTransferPayment($config['account_number']);
            default:
                throw new Exception("Unknown payment type: $type");
        }
    }
}

class ShippingFactory {
    public static function create(string $type): ShippingStrategy {
        switch (strtolower($type)) {
            case 'express':
                return new ExpressShipping();
            case 'standard':
                return new StandardShipping();
            case 'economy':
                return new EconomyShipping();
            default:
                throw new Exception("Unknown shipping type: $type");
        }
    }
}

// ===============================================================================
// 6. FACTORY USAGE EXAMPLE
// ===============================================================================

echo "🏭 FACTORY PATTERN WITH STRATEGIES\n";
echo str_repeat("=", 50) . "\n\n";

// Payment factory usage
try {
    $creditCard = PaymentFactory::create('credit_card', ['card_number' => '4532123456789012']);
    $processor = new PaymentProcessor();
    $processor->setPaymentMethod($creditCard);
    
    echo "Factory Created Payment:\n";
    echo $processor->processPayment(50.00) . "\n\n";
    
    // Shipping factory usage
    $express = ShippingFactory::create('express');
    $calculator = new ShippingCalculator();
    $calculator->setShippingMethod($express);
    
    $result = $calculator->calculate(2.0);
    echo "Factory Created Shipping:\n";
    echo "Cost: $" . number_format($result['cost'], 2) . ", Time: {$result['delivery_time']}\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// ===============================================================================
// 7. REAL-WORLD E-COMMERCE EXAMPLE
// ===============================================================================

class Order {
    private $items = [];
    private $total = 0;
    private $paymentProcessor;
    private $shippingCalculator;
    
    public function __construct() {
        $this->paymentProcessor = new PaymentProcessor();
        $this->shippingCalculator = new ShippingCalculator();
    }
    
    public function addItem(string $name, float $price): void {
        $this->items[] = ['name' => $name, 'price' => $price];
        $this->total += $price;
    }
    
    public function setPaymentMethod(string $type, array $config): void {
        $strategy = PaymentFactory::create($type, $config);
        $this->paymentProcessor->setPaymentMethod($strategy);
    }
    
    public function setShippingMethod(string $type): void {
        $strategy = ShippingFactory::create($type);
        $this->shippingCalculator->setShippingMethod($strategy);
    }
    
    public function checkout(float $weight): array {
        // Calculate shipping
        $shipping = $this->shippingCalculator->calculate($weight);
        $shippingCost = $shipping['cost'];
        
        // Calculate total with shipping
        $grandTotal = $this->total + $shippingCost;
        
        // Process payment
        $paymentResult = $this->paymentProcessor->processPayment($grandTotal);
        
        return [
            'items_total' => $this->total,
            'shipping_cost' => $shippingCost,
            'grand_total' => $grandTotal,
            'payment_result' => $paymentResult,
            'delivery_time' => $shipping['delivery_time']
        ];
    }
}

// ===============================================================================
// 8. E-COMMERCE EXAMPLE USAGE
// ===============================================================================

echo "🛒 E-COMMERCE ORDER EXAMPLE\n";
echo str_repeat("=", 50) . "\n\n";

$order = new Order();

// Add items to cart
$order->addItem('Laptop', 899.99);
$order->addItem('Mouse', 29.99);

// Set payment method
$order->setPaymentMethod('paypal', ['email' => 'customer@example.com']);

// Set shipping method
$order->setShippingMethod('express');

// Checkout
$result = $order->checkout(3.0); // 3 pounds weight

echo "ORDER SUMMARY:\n";
echo "Items Total: $" . number_format($result['items_total'], 2) . "\n";
echo "Shipping: $" . number_format($result['shipping_cost'], 2) . "\n";
echo "Grand Total: $" . number_format($result['grand_total'], 2) . "\n";
echo "Payment: {$result['payment_result']}\n";
echo "Delivery: {$result['delivery_time']}\n\n";

// ===============================================================================
// 9. CONDITIONAL VS STRATEGY COMPARISON
// ===============================================================================

echo "🔄 CONDITIONAL vs STRATEGY COMPARISON\n";
echo str_repeat("=", 50) . "\n\n";

// BAD: Using conditionals (Hard to maintain)
class BadPaymentProcessor {
    public function processPayment(string $type, float $amount, array $config): string {
        if ($type === 'credit_card') {
            $fee = $amount * 0.03;
            return "Credit Card: $" . number_format($amount + $fee, 2);
        } elseif ($type === 'paypal') {
            $fee = $amount * 0.034 + 0.30;
            return "PayPal: $" . number_format($amount + $fee, 2);
        } elseif ($type === 'bank_transfer') {
            $fee = 1.50;
            return "Bank Transfer: $" . number_format($amount + $fee, 2);
        } else {
            return "Unknown payment type";
        }
    }
}

// GOOD: Using Strategy Pattern (Easy to maintain and extend)
// Already implemented above!

echo "❌ BAD APPROACH (Conditional):\n";
$badProcessor = new BadPaymentProcessor();
echo $badProcessor->processPayment('credit_card', 100, []) . "\n";
echo $badProcessor->processPayment('paypal', 100, []) . "\n\n";

echo "✅ GOOD APPROACH (Strategy Pattern):\n";
$goodProcessor = new PaymentProcessor();
$goodProcessor->setPaymentMethod(new CreditCardPayment('1234567890123456'));
echo $goodProcessor->processPayment(100) . "\n";

$goodProcessor->setPaymentMethod(new PayPalPayment('user@example.com'));
echo $goodProcessor->processPayment(100) . "\n\n";

// ===============================================================================
// 10. SUMMARY & BENEFITS
// ===============================================================================

echo "📚 STRATEGY PATTERN SUMMARY\n";
echo str_repeat("=", 50) . "\n\n";

echo "✅ KEY BENEFITS:\n";
echo "1. Easy to add new algorithms (Open/Closed Principle)\n";
echo "2. Runtime algorithm switching\n";
echo "3. Eliminates if/else chains\n";
echo "4. Each algorithm is independently testable\n";
echo "5. Clean, maintainable code\n\n";

echo "🎯 WHEN TO USE:\n";
echo "1. Multiple ways to do the same task\n";
echo "2. Need to switch algorithms at runtime\n";
echo "3. Want to avoid complex conditionals\n";
echo "4. Algorithms may change frequently\n\n";

echo "⚠️ WHEN NOT TO USE:\n";
echo "1. Only 2-3 simple options\n";
echo "2. Algorithms never change\n";
echo "3. Performance is critical\n\n";

echo "🔧 IMPLEMENTATION STEPS:\n";
echo "1. Create Strategy Interface\n";
echo "2. Implement Concrete Strategies\n";
echo "3. Create Context Class\n";
echo "4. Use Factory for Strategy Creation (Optional)\n\n";

echo "🏆 REAL-WORLD EXAMPLES:\n";
echo "• Payment Gateways (Stripe, PayPal, Square)\n";
echo "• Shipping Methods (FedEx, UPS, USPS)\n";
echo "• Authentication (OAuth, LDAP, Database)\n";
echo "• File Storage (Local, S3, Google Drive)\n";
echo "• Notification (Email, SMS, Push)\n\n";

echo "🎉 STRATEGY PATTERN TUTORIAL COMPLETE!\n";
echo "এখন আপনি Strategy Pattern দিয়ে flexible এবং maintainable code লিখতে পারবেন।\n";

/*
===============================================================================
QUICK REFERENCE:
===============================================================================

STRUCTURE:
----------
1. Strategy Interface - Common contract
2. Concrete Strategies - Different implementations  
3. Context Class - Uses strategies
4. Client - Selects strategy

BENEFITS:
---------
✅ Open/Closed Principle
✅ Runtime switching
✅ No conditionals
✅ Easy testing
✅ Clean code

EXAMPLE USE CASES:
------------------
• Payment methods
• Shipping options  
• Sorting algorithms
• Authentication methods
• File upload strategies
• Pricing strategies

Remember: "Same job, different ways!"
===============================================================================
*/
