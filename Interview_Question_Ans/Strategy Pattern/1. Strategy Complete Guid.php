<?php

/*
===============================================================================
STRATEGY PATTERN - সম্পূর্ণ নোট ও ব্যাখ্যা
===============================================================================

STRATEGY PATTERN কি?
--------------------
Strategy Pattern হলো একটি Behavioral Design Pattern যেখানে:
- একটি family of algorithms define করা হয়
- প্রতিটি algorithm কে encapsulate করা হয়
- Runtime এ algorithms গুলো interchangeable করা হয়
- Client code algorithm implementation জানে না

কেন ব্যবহার করব?
------------------
✅ Multiple ways to perform a task
✅ Runtime এ algorithm change করা যায়
✅ Open/Closed Principle - নতুন strategy add করা যায় existing code modify না করে
✅ Eliminates conditional statements (if/else, switch)
✅ Easy testing - প্রতিটি strategy independently test করা যায়
✅ Single Responsibility - প্রতিটি strategy একটি specific algorithm handle করে

REAL-WORLD EXAMPLES:
-------------------
💳 Payment Methods - Credit Card, PayPal, Bank Transfer
📦 Shipping Methods - Express, Standard, Economy
🔐 Encryption Algorithms - AES, DES, RSA
🗂️ Sorting Algorithms - QuickSort, MergeSort, BubbleSort
📊 Report Generation - PDF, Excel, CSV
🎮 Game AI - Aggressive, Defensive, Balanced
💰 Pricing Strategies - Regular, Discount, Premium
📱 Notification Methods - SMS, Email, Push
*/

// ===============================================================================
// STRATEGY INTERFACE - ALGORITHM CONTRACT
// ===============================================================================

/*
STRATEGY INTERFACE EXPLANATION:
------------------------------
এই interface সব concrete strategy classes implement করবে।
Algorithm এর common method signature define করে।
*/

/*
PAYMENT STRATEGY EXAMPLE:
------------------------
বিভিন্ন payment methods এর জন্য strategy interface।
*/
interface PaymentStrategyInterface {
    /*
    PROCESS PAYMENT METHOD:
    ----------------------
    Purpose: Payment process করা
    
    Parameters:
    - float $amount: Payment amount
    - array $details: Payment related details
    
    Return: array - Payment result with status and transaction info
    */
    public function processPayment(float $amount, array $details): array;
    
    /*
    VALIDATE METHOD:
    ---------------
    Purpose: Payment details validate করা
    
    Parameters:
    - array $details: Payment details to validate
    
    Return: bool - Validation result
    */
    public function validate(array $details): bool;
    
    /*
    GET FEES METHOD:
    ---------------
    Purpose: Payment processing fees calculate করা
    
    Parameters:
    - float $amount: Payment amount
    
    Return: float - Processing fees
    */
    public function getFees(float $amount): float;
}

// ===============================================================================
// CONCRETE STRATEGY IMPLEMENTATIONS
// ===============================================================================

/*
CREDIT CARD PAYMENT STRATEGY:
-----------------------------
Credit card payment processing এর জন্য concrete strategy।
*/
class CreditCardPaymentStrategy implements PaymentStrategyInterface {
    private $gatewayUrl;
    private $merchantId;
    
    public function __construct(string $gatewayUrl = 'https://api.creditcard.com', string $merchantId = 'MERCHANT123') {
        $this->gatewayUrl = $gatewayUrl;
        $this->merchantId = $merchantId;
        echo "💳 CreditCardPaymentStrategy initialized - Gateway: $gatewayUrl\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        echo "💳 [CREDIT CARD] Processing payment of $" . number_format($amount, 2) . "\n";
        
        // Validate card details
        if (!$this->validate($details)) {
            return [
                'status' => 'failed',
                'message' => 'Invalid credit card details',
                'transaction_id' => null
            ];
        }
        
        // Calculate fees
        $fees = $this->getFees($amount);
        $totalAmount = $amount + $fees;
        
        echo "💳 Card Number: **** **** **** " . substr($details['card_number'], -4) . "\n";
        echo "💳 Cardholder: {$details['cardholder_name']}\n";
        echo "💳 Amount: $" . number_format($amount, 2) . "\n";
        echo "💳 Fees: $" . number_format($fees, 2) . "\n";
        echo "💳 Total: $" . number_format($totalAmount, 2) . "\n";
        
        // Simulate API call to payment gateway
        echo "💳 Connecting to gateway: $this->gatewayUrl\n";
        echo "💳 Merchant ID: $this->merchantId\n";
        
        // Mock processing delay
        echo "💳 Processing...\n";
        usleep(100000); // 0.1 second delay
        
        // Simulate successful payment
        $transactionId = 'CC_' . strtoupper(uniqid());
        
        echo "✅ Credit card payment successful!\n";
        
        return [
            'status' => 'success',
            'message' => 'Credit card payment processed successfully',
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fees' => $fees,
            'total' => $totalAmount,
            'payment_method' => 'credit_card',
            'last_four' => substr($details['card_number'], -4)
        ];
    }
    
    public function validate(array $details): bool {
        echo "💳 Validating credit card details...\n";
        
        // Required fields check
        $requiredFields = ['card_number', 'cardholder_name', 'expiry_month', 'expiry_year', 'cvv'];
        
        foreach ($requiredFields as $field) {
            if (!isset($details[$field]) || empty($details[$field])) {
                echo "❌ Missing required field: $field\n";
                return false;
            }
        }
        
        // Card number validation (simplified Luhn algorithm)
        if (!$this->validateCardNumber($details['card_number'])) {
            echo "❌ Invalid card number\n";
            return false;
        }
        
        // Expiry date validation
        if (!$this->validateExpiryDate($details['expiry_month'], $details['expiry_year'])) {
            echo "❌ Card expired or invalid expiry date\n";
            return false;
        }
        
        // CVV validation
        if (!$this->validateCVV($details['cvv'])) {
            echo "❌ Invalid CVV\n";
            return false;
        }
        
        echo "✅ Credit card details are valid\n";
        return true;
    }
    
    public function getFees(float $amount): float {
        // Credit card processing fee: 2.9% + $0.30
        $percentageFee = $amount * 0.029;
        $fixedFee = 0.30;
        return round($percentageFee + $fixedFee, 2);
    }
    
    private function validateCardNumber(string $cardNumber): bool {
        // Remove spaces and dashes
        $cardNumber = preg_replace('/[^0-9]/', '', $cardNumber);
        
        // Check length (13-19 digits)
        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }
        
        // Simplified validation - just check if all digits
        return ctype_digit($cardNumber);
    }
    
    private function validateExpiryDate(string $month, string $year): bool {
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        
        $expiryYear = (int)$year;
        $expiryMonth = (int)$month;
        
        if ($expiryYear < $currentYear) {
            return false;
        }
        
        if ($expiryYear == $currentYear && $expiryMonth < $currentMonth) {
            return false;
        }
        
        return $expiryMonth >= 1 && $expiryMonth <= 12;
    }
    
    private function validateCVV(string $cvv): bool {
        return ctype_digit($cvv) && (strlen($cvv) === 3 || strlen($cvv) === 4);
    }
}

/*
PAYPAL PAYMENT STRATEGY:
------------------------
PayPal payment processing এর জন্য concrete strategy।
*/
class PayPalPaymentStrategy implements PaymentStrategyInterface {
    private $apiUrl;
    private $clientId;
    
    public function __construct(string $apiUrl = 'https://api.paypal.com', string $clientId = 'PAYPAL_CLIENT_123') {
        $this->apiUrl = $apiUrl;
        $this->clientId = $clientId;
        echo "🅿️ PayPalPaymentStrategy initialized - API: $apiUrl\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        echo "🅿️ [PAYPAL] Processing payment of $" . number_format($amount, 2) . "\n";
        
        // Validate PayPal details
        if (!$this->validate($details)) {
            return [
                'status' => 'failed',
                'message' => 'Invalid PayPal details',
                'transaction_id' => null
            ];
        }
        
        // Calculate fees
        $fees = $this->getFees($amount);
        $totalAmount = $amount + $fees;
        
        echo "🅿️ PayPal Email: {$details['email']}\n";
        echo "🅿️ Amount: $" . number_format($amount, 2) . "\n";
        echo "🅿️ Fees: $" . number_format($fees, 2) . "\n";
        echo "🅿️ Total: $" . number_format($totalAmount, 2) . "\n";
        
        // Simulate PayPal API call
        echo "🅿️ Connecting to PayPal API: $this->apiUrl\n";
        echo "🅿️ Client ID: $this->clientId\n";
        echo "🅿️ Redirecting to PayPal for authentication...\n";
        
        // Mock processing delay
        echo "🅿️ Processing...\n";
        usleep(150000); // 0.15 second delay
        
        // Simulate successful payment
        $transactionId = 'PP_' . strtoupper(uniqid());
        
        echo "✅ PayPal payment successful!\n";
        
        return [
            'status' => 'success',
            'message' => 'PayPal payment processed successfully',
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fees' => $fees,
            'total' => $totalAmount,
            'payment_method' => 'paypal',
            'payer_email' => $details['email']
        ];
    }
    
    public function validate(array $details): bool {
        echo "🅿️ Validating PayPal details...\n";
        
        // Check required fields
        if (!isset($details['email']) || empty($details['email'])) {
            echo "❌ PayPal email is required\n";
            return false;
        }
        
        // Validate email format
        if (!filter_var($details['email'], FILTER_VALIDATE_EMAIL)) {
            echo "❌ Invalid email format\n";
            return false;
        }
        
        echo "✅ PayPal details are valid\n";
        return true;
    }
    
    public function getFees(float $amount): float {
        // PayPal fee: 3.4% + $0.30 for domestic transactions
        $percentageFee = $amount * 0.034;
        $fixedFee = 0.30;
        return round($percentageFee + $fixedFee, 2);
    }
}

/*
BANK TRANSFER PAYMENT STRATEGY:
------------------------------
Bank transfer payment processing এর জন্য concrete strategy।
*/
class BankTransferPaymentStrategy implements PaymentStrategyInterface {
    private $bankingApi;
    private $bankCode;
    
    public function __construct(string $bankingApi = 'https://api.bank.com', string $bankCode = 'BANK001') {
        $this->bankingApi = $bankingApi;
        $this->bankCode = $bankCode;
        echo "🏦 BankTransferPaymentStrategy initialized - API: $bankingApi\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        echo "🏦 [BANK TRANSFER] Processing payment of $" . number_format($amount, 2) . "\n";
        
        // Validate bank details
        if (!$this->validate($details)) {
            return [
                'status' => 'failed',
                'message' => 'Invalid bank transfer details',
                'transaction_id' => null
            ];
        }
        
        // Calculate fees
        $fees = $this->getFees($amount);
        $totalAmount = $amount + $fees;
        
        echo "🏦 Account Number: **** **** " . substr($details['account_number'], -4) . "\n";
        echo "🏦 Bank Name: {$details['bank_name']}\n";
        echo "🏦 Account Holder: {$details['account_holder']}\n";
        echo "🏦 Amount: $" . number_format($amount, 2) . "\n";
        echo "🏦 Fees: $" . number_format($fees, 2) . "\n";
        echo "🏦 Total: $" . number_format($totalAmount, 2) . "\n";
        
        // Simulate bank API call
        echo "🏦 Connecting to banking API: $this->bankingApi\n";
        echo "🏦 Bank Code: $this->bankCode\n";
        echo "🏦 Initiating ACH transfer...\n";
        
        // Mock processing delay (bank transfers take longer)
        echo "🏦 Processing... (Bank transfers may take 1-3 business days)\n";
        usleep(200000); // 0.2 second delay
        
        // Simulate successful transfer initiation
        $transactionId = 'BT_' . strtoupper(uniqid());
        
        echo "✅ Bank transfer initiated successfully!\n";
        
        return [
            'status' => 'success',
            'message' => 'Bank transfer initiated successfully',
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fees' => $fees,
            'total' => $totalAmount,
            'payment_method' => 'bank_transfer',
            'account_last_four' => substr($details['account_number'], -4),
            'estimated_completion' => date('Y-m-d', strtotime('+3 business days'))
        ];
    }
    
    public function validate(array $details): bool {
        echo "🏦 Validating bank transfer details...\n";
        
        // Required fields check
        $requiredFields = ['account_number', 'routing_number', 'account_holder', 'bank_name'];
        
        foreach ($requiredFields as $field) {
            if (!isset($details[$field]) || empty($details[$field])) {
                echo "❌ Missing required field: $field\n";
                return false;
            }
        }
        
        // Account number validation
        if (!$this->validateAccountNumber($details['account_number'])) {
            echo "❌ Invalid account number\n";
            return false;
        }
        
        // Routing number validation
        if (!$this->validateRoutingNumber($details['routing_number'])) {
            echo "❌ Invalid routing number\n";
            return false;
        }
        
        echo "✅ Bank transfer details are valid\n";
        return true;
    }
    
    public function getFees(float $amount): float {
        // Bank transfer fee: Fixed $1.50 for ACH transfers
        return 1.50;
    }
    
    private function validateAccountNumber(string $accountNumber): bool {
        // Remove any non-digits
        $accountNumber = preg_replace('/[^0-9]/', '', $accountNumber);
        
        // Check length (typically 8-12 digits)
        return strlen($accountNumber) >= 8 && strlen($accountNumber) <= 12 && ctype_digit($accountNumber);
    }
    
    private function validateRoutingNumber(string $routingNumber): bool {
        // Remove any non-digits
        $routingNumber = preg_replace('/[^0-9]/', '', $routingNumber);
        
        // Routing numbers are exactly 9 digits
        return strlen($routingNumber) === 9 && ctype_digit($routingNumber);
    }
}

/*
CRYPTOCURRENCY PAYMENT STRATEGY:
-------------------------------
Cryptocurrency payment processing এর জন্য concrete strategy।
*/
class CryptocurrencyPaymentStrategy implements PaymentStrategyInterface {
    private $blockchainApi;
    private $supportedCurrencies;
    
    public function __construct(string $blockchainApi = 'https://api.blockchain.com', array $supportedCurrencies = ['BTC', 'ETH', 'LTC']) {
        $this->blockchainApi = $blockchainApi;
        $this->supportedCurrencies = $supportedCurrencies;
        echo "₿ CryptocurrencyPaymentStrategy initialized - API: $blockchainApi\n";
        echo "₿ Supported currencies: " . implode(', ', $supportedCurrencies) . "\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        echo "₿ [CRYPTOCURRENCY] Processing payment of $" . number_format($amount, 2) . "\n";
        
        // Validate crypto details
        if (!$this->validate($details)) {
            return [
                'status' => 'failed',
                'message' => 'Invalid cryptocurrency details',
                'transaction_id' => null
            ];
        }
        
        // Calculate fees
        $fees = $this->getFees($amount);
        $totalAmount = $amount + $fees;
        
        echo "₿ Currency: {$details['currency']}\n";
        echo "₿ Wallet Address: {$details['wallet_address']}\n";
        echo "₿ Amount: $" . number_format($amount, 2) . "\n";
        echo "₿ Network Fees: $" . number_format($fees, 2) . "\n";
        echo "₿ Total: $" . number_format($totalAmount, 2) . "\n";
        
        // Convert USD to cryptocurrency
        $cryptoAmount = $this->convertToCrypto($amount, $details['currency']);
        echo "₿ Crypto Amount: {$cryptoAmount} {$details['currency']}\n";
        
        // Simulate blockchain API call
        echo "₿ Connecting to blockchain API: $this->blockchainApi\n";
        echo "₿ Broadcasting transaction to network...\n";
        
        // Mock processing delay (blockchain confirmation)
        echo "₿ Waiting for network confirmation...\n";
        usleep(300000); // 0.3 second delay
        
        // Simulate successful transaction
        $transactionId = 'CRYPTO_' . strtoupper(uniqid());
        $blockHash = 'BLOCK_' . strtoupper(uniqid());
        
        echo "✅ Cryptocurrency payment successful!\n";
        
        return [
            'status' => 'success',
            'message' => 'Cryptocurrency payment processed successfully',
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fees' => $fees,
            'total' => $totalAmount,
            'payment_method' => 'cryptocurrency',
            'currency' => $details['currency'],
            'crypto_amount' => $cryptoAmount,
            'wallet_address' => $details['wallet_address'],
            'block_hash' => $blockHash,
            'confirmations' => 1
        ];
    }
    
    public function validate(array $details): bool {
        echo "₿ Validating cryptocurrency details...\n";
        
        // Required fields check
        $requiredFields = ['currency', 'wallet_address'];
        
        foreach ($requiredFields as $field) {
            if (!isset($details[$field]) || empty($details[$field])) {
                echo "❌ Missing required field: $field\n";
                return false;
            }
        }
        
        // Check if currency is supported
        if (!in_array($details['currency'], $this->supportedCurrencies)) {
            echo "❌ Unsupported cryptocurrency: {$details['currency']}\n";
            echo "   Supported currencies: " . implode(', ', $this->supportedCurrencies) . "\n";
            return false;
        }
        
        // Validate wallet address format (simplified)
        if (!$this->validateWalletAddress($details['wallet_address'], $details['currency'])) {
            echo "❌ Invalid wallet address for {$details['currency']}\n";
            return false;
        }
        
        echo "✅ Cryptocurrency details are valid\n";
        return true;
    }
    
    public function getFees(float $amount): float {
        // Cryptocurrency network fees: 1% of transaction amount
        return round($amount * 0.01, 2);
    }
    
    private function validateWalletAddress(string $address, string $currency): bool {
        // Simplified wallet address validation
        switch ($currency) {
            case 'BTC':
                // Bitcoin addresses start with 1, 3, or bc1
                return preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$|^bc1[a-z0-9]{39,59}$/', $address);
            case 'ETH':
                // Ethereum addresses start with 0x and are 42 characters long
                return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
            case 'LTC':
                // Litecoin addresses start with L or M
                return preg_match('/^[LM][a-km-zA-HJ-NP-Z1-9]{26,33}$/', $address);
            default:
                return false;
        }
    }
    
    private function convertToCrypto(float $usdAmount, string $currency): float {
        // Mock conversion rates (in real app, fetch from API)
        $rates = [
            'BTC' => 45000.00,  // 1 BTC = $45,000
            'ETH' => 3000.00,   // 1 ETH = $3,000
            'LTC' => 150.00     // 1 LTC = $150
        ];
        
        if (!isset($rates[$currency])) {
            return 0;
        }
        
        return round($usdAmount / $rates[$currency], 8);
    }
}

// ===============================================================================
// CONTEXT CLASS - STRATEGY PATTERN CLIENT
// ===============================================================================

/*
PAYMENT PROCESSOR CONTEXT:
--------------------------
Strategy pattern এর context class।
Different payment strategies ব্যবহার করে payment process করে।
*/
class PaymentProcessor {
    private $paymentStrategy;
    private $transactionHistory = [];
    
    /*
    CONSTRUCTOR:
    -----------
    Initial payment strategy set করা (optional)।
    */
    public function __construct(PaymentStrategyInterface $paymentStrategy = null) {
        if ($paymentStrategy) {
            $this->paymentStrategy = $paymentStrategy;
            echo "💰 PaymentProcessor initialized with strategy: " . get_class($paymentStrategy) . "\n";
        } else {
            echo "💰 PaymentProcessor initialized without strategy\n";
        }
    }
    
    /*
    SET STRATEGY METHOD:
    -------------------
    Runtime এ payment strategy change করার জন্য।
    এটি Strategy Pattern এর core feature।
    */
    public function setPaymentStrategy(PaymentStrategyInterface $strategy): void {
        $this->paymentStrategy = $strategy;
        $strategyName = get_class($strategy);
        echo "🔄 Payment strategy changed to: $strategyName\n";
    }
    
    /*
    PROCESS PAYMENT METHOD:
    ----------------------
    Current strategy ব্যবহার করে payment process করা।
    */
    public function processPayment(float $amount, array $paymentDetails): array {
        if (!$this->paymentStrategy) {
            echo "❌ No payment strategy set!\n";
            return [
                'status' => 'failed',
                'message' => 'No payment strategy configured'
            ];
        }
        
        echo "\n💰 [PAYMENT PROCESSOR] Starting payment process...\n";
        echo "💰 Amount: $" . number_format($amount, 2) . "\n";
        echo "💰 Strategy: " . get_class($this->paymentStrategy) . "\n";
        
        // Record start time
        $startTime = microtime(true);
        
        // Process payment using current strategy
        $result = $this->paymentStrategy->processPayment($amount, $paymentDetails);
        
        // Record end time
        $endTime = microtime(true);
        $processingTime = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds
        
        // Add processing metadata
        $result['processing_time_ms'] = $processingTime;
        $result['strategy_used'] = get_class($this->paymentStrategy);
        $result['timestamp'] = date('Y-m-d H:i:s');
        
        // Store in transaction history
        $this->transactionHistory[] = $result;
        
        echo "💰 Processing completed in {$processingTime}ms\n";
        echo "💰 Transaction recorded in history\n\n";
        
        return $result;
    }
    
    /*
    CALCULATE FEES METHOD:
    ---------------------
    Current strategy এর fees calculate করা।
    */
    public function calculateFees(float $amount): float {
        if (!$this->paymentStrategy) {
            echo "❌ No payment strategy set for fee calculation!\n";
            return 0;
        }
        
        return $this->paymentStrategy->getFees($amount);
    }
    
    /*
    VALIDATE PAYMENT DETAILS METHOD:
    -------------------------------
    Current strategy দিয়ে payment details validate করা।
    */
    public function validatePaymentDetails(array $details): bool {
        if (!$this->paymentStrategy) {
            echo "❌ No payment strategy set for validation!\n";
            return false;
        }
        
        return $this->paymentStrategy->validate($details);
    }
    
    /*
    GET TRANSACTION HISTORY METHOD:
    ------------------------------
    সব processed transactions এর history return করা।
    */
    public function getTransactionHistory(): array {
        return $this->transactionHistory;
    }
    
    /*
    GET CURRENT STRATEGY METHOD:
    ---------------------------
    Currently set strategy এর information return করা।
    */
    public function getCurrentStrategy(): ?string {
        return $this->paymentStrategy ? get_class($this->paymentStrategy) : null;
    }
    
    /*
    CLEAR HISTORY METHOD:
    --------------------
    Transaction history clear করা।
    */
    public function clearTransactionHistory(): void {
        $this->transactionHistory = [];
        echo "🗑️ Transaction history cleared\n";
    }
}

// ===============================================================================
// STRATEGY FACTORY - STRATEGY CREATION MANAGEMENT
// ===============================================================================

/*
PAYMENT STRATEGY FACTORY:
-------------------------
Different payment strategies create করার জন্য factory class।
Strategy creation logic centralize করে।
*/
class PaymentStrategyFactory {
    private static $strategies = [];
    
    /*
    CREATE STRATEGY METHOD:
    ----------------------
    Strategy type based on strategy create করা।
    */
    public static function createStrategy(string $type, array $config = []): PaymentStrategyInterface {
        echo "🏭 [STRATEGY FACTORY] Creating strategy: $type\n";
        
        switch (strtolower($type)) {
            case 'credit_card':
            case 'creditcard':
                $gateway = $config['gateway_url'] ?? 'https://api.creditcard.com';
                $merchantId = $config['merchant_id'] ?? 'MERCHANT123';
                return new CreditCardPaymentStrategy($gateway, $merchantId);
                
            case 'paypal':
                $apiUrl = $config['api_url'] ?? 'https://api.paypal.com';
                $clientId = $config['client_id'] ?? 'PAYPAL_CLIENT_123';
                return new PayPalPaymentStrategy($apiUrl, $clientId);
                
            case 'bank_transfer':
            case 'banktransfer':
                $bankingApi = $config['banking_api'] ?? 'https://api.bank.com';
                $bankCode = $config['bank_code'] ?? 'BANK001';
                return new BankTransferPaymentStrategy($bankingApi, $bankCode);
                
            case 'crypto':
            case 'cryptocurrency':
                $blockchainApi = $config['blockchain_api'] ?? 'https://api.blockchain.com';
                $currencies = $config['supported_currencies'] ?? ['BTC', 'ETH', 'LTC'];
                return new CryptocurrencyPaymentStrategy($blockchainApi, $currencies);
                
            default:
                throw new InvalidArgumentException("❌ Unknown payment strategy type: $type");
        }
    }
    
    /*
    GET AVAILABLE STRATEGIES METHOD:
    -------------------------------
    Available strategy types return করা।
    */
    public static function getAvailableStrategies(): array {
        return [
            'credit_card' => 'Credit Card Payment',
            'paypal' => 'PayPal Payment',
            'bank_transfer' => 'Bank Transfer',
            'cryptocurrency' => 'Cryptocurrency Payment'
        ];
    }
    
    /*
    CREATE MULTIPLE STRATEGIES METHOD:
    ---------------------------------
    Multiple strategies একসাথে create করা।
    */
    public static function createMultipleStrategies(array $types, array $configs = []): array {
        $strategies = [];
        
        foreach ($types as $type) {
            $config = $configs[$type] ?? [];
            $strategies[$type] = self::createStrategy($type, $config);
        }
        
        return $strategies;
    }
}

// ===============================================================================
// ADVANCED STRATEGY EXAMPLES
// ===============================================================================

/*
SHIPPING STRATEGY INTERFACE:
---------------------------
Shipping methods এর জন্য strategy interface।
*/
interface ShippingStrategyInterface {
    public function calculateCost(float $weight, string $destination): float;
    public function getDeliveryTime(string $destination): string;
    public function getTrackingInfo(): array;
}

/*
EXPRESS SHIPPING STRATEGY:
-------------------------
Express shipping এর জন্য concrete strategy।
*/
class ExpressShippingStrategy implements ShippingStrategyInterface {
    private $baseRate;
    private $weightMultiplier;
    
    public function __construct(float $baseRate = 15.00, float $weightMultiplier = 2.50) {
        $this->baseRate = $baseRate;
        $this->weightMultiplier = $weightMultiplier;
        echo "🚀 ExpressShippingStrategy initialized - Base: $" . number_format($baseRate, 2) . "\n";
    }
    
    public function calculateCost(float $weight, string $destination): float {
        echo "🚀 [EXPRESS SHIPPING] Calculating cost...\n";
        echo "🚀 Weight: {$weight} lbs\n";
        echo "🚀 Destination: $destination\n";
        
        $cost = $this->baseRate + ($weight * $this->weightMultiplier);
        
        // International shipping surcharge
        if ($this->isInternational($destination)) {
            $cost += 25.00;
            echo "🚀 International surcharge: $25.00\n";
        }
        
        echo "🚀 Total cost: $" . number_format($cost, 2) . "\n";
        return round($cost, 2);
    }
    
    public function getDeliveryTime(string $destination): string {
        if ($this->isInternational($destination)) {
            return "1-2 business days";
        }
        return "Next business day";
    }
    
    public function getTrackingInfo(): array {
        return [
            'tracking_number' => 'EXP_' . strtoupper(uniqid()),
            'real_time_tracking' => true,
            'signature_required' => true,
            'insurance_included' => true
        ];
    }
    
    private function isInternational(string $destination): bool {
        $internationalCodes = ['CA', 'MX', 'UK', 'FR', 'DE', 'JP', 'AU'];
        return in_array(strtoupper($destination), $internationalCodes);
    }
}

/*
STANDARD SHIPPING STRATEGY:
---------------------------
Standard shipping এর জন্য concrete strategy।
*/
class StandardShippingStrategy implements ShippingStrategyInterface {
    private $baseRate;
    private $weightMultiplier;
    
    public function __construct(float $baseRate = 8.00, float $weightMultiplier = 1.20) {
        $this->baseRate = $baseRate;
        $this->weightMultiplier = $weightMultiplier;
        echo "📦 StandardShippingStrategy initialized - Base: $" . number_format($baseRate, 2) . "\n";
    }
    
    public function calculateCost(float $weight, string $destination): float {
        echo "📦 [STANDARD SHIPPING] Calculating cost...\n";
        echo "📦 Weight: {$weight} lbs\n";
        echo "📦 Destination: $destination\n";
        
        $cost = $this->baseRate + ($weight * $this->weightMultiplier);
        
        // International shipping surcharge
        if ($this->isInternational($destination)) {
            $cost += 15.00;
            echo "📦 International surcharge: $15.00\n";
        }
        
        echo "📦 Total cost: $" . number_format($cost, 2) . "\n";
        return round($cost, 2);
    }
    
    public function getDeliveryTime(string $destination): string {
        if ($this->isInternational($destination)) {
            return "5-10 business days";
        }
        return "3-5 business days";
    }
    
    public function getTrackingInfo(): array {
        return [
            'tracking_number' => 'STD_' . strtoupper(uniqid()),
            'real_time_tracking' => false,
            'signature_required' => false,
            'insurance_included' => false
        ];
    }
    
    private function isInternational(string $destination): bool {
        $internationalCodes = ['CA', 'MX', 'UK', 'FR', 'DE', 'JP', 'AU'];
        return in_array(strtoupper($destination), $internationalCodes);
    }
}

/*
ECONOMY SHIPPING STRATEGY:
-------------------------
Economy shipping এর জন্য concrete strategy।
*/
class EconomyShippingStrategy implements ShippingStrategyInterface {
    private $baseRate;
    private $weightMultiplier;
    
    public function __construct(float $baseRate = 5.00, float $weightMultiplier = 0.80) {
        $this->baseRate = $baseRate;
        $this->weightMultiplier = $weightMultiplier;
        echo "🐌 EconomyShippingStrategy initialized - Base: $" . number_format($baseRate, 2) . "\n";
    }
    
    public function calculateCost(float $weight, string $destination): float {
        echo "🐌 [ECONOMY SHIPPING] Calculating cost...\n";
        echo "🐌 Weight: {$weight} lbs\n";
        echo "🐌 Destination: $destination\n";
        
        $cost = $this->baseRate + ($weight * $this->weightMultiplier);
        
        // International shipping surcharge
        if ($this->isInternational($destination)) {
            $cost += 10.00;
            echo "🐌 International surcharge: $10.00\n";
        }
        
        echo "🐌 Total cost: $" . number_format($cost, 2) . "\n";
        return round($cost, 2);
    }
    
    public function getDeliveryTime(string $destination): string {
        if ($this->isInternational($destination)) {
            return "2-4 weeks";
        }
        return "7-14 business days";
    }
    
    public function getTrackingInfo(): array {
        return [
            'tracking_number' => 'ECO_' . strtoupper(uniqid()),
            'real_time_tracking' => false,
            'signature_required' => false,
            'insurance_included' => false
        ];
    }
    
    private function isInternational(string $destination): bool {
        $internationalCodes = ['CA', 'MX', 'UK', 'FR', 'DE', 'JP', 'AU'];
        return in_array(strtoupper($destination), $internationalCodes);
    }
}

/*
SHIPPING CALCULATOR CONTEXT:
---------------------------
Shipping cost calculation এর জন্য context class।
*/
class ShippingCalculator {
    private $shippingStrategy;
    private $calculations = [];
    
    public function __construct(ShippingStrategyInterface $shippingStrategy = null) {
        if ($shippingStrategy) {
            $this->shippingStrategy = $shippingStrategy;
            echo "📋 ShippingCalculator initialized with strategy: " . get_class($shippingStrategy) . "\n";
        }
    }
    
    public function setShippingStrategy(ShippingStrategyInterface $strategy): void {
        $this->shippingStrategy = $strategy;
        echo "🔄 Shipping strategy changed to: " . get_class($strategy) . "\n";
    }
    
    public function calculateShipping(float $weight, string $destination): array {
        if (!$this->shippingStrategy) {
            throw new Exception("No shipping strategy set!");
        }
        
        echo "\n📋 [SHIPPING CALCULATOR] Calculating shipping...\n";
        
        $cost = $this->shippingStrategy->calculateCost($weight, $destination);
        $deliveryTime = $this->shippingStrategy->getDeliveryTime($destination);
        $trackingInfo = $this->shippingStrategy->getTrackingInfo();
        
        $result = [
            'cost' => $cost,
            'delivery_time' => $deliveryTime,
            'tracking_info' => $trackingInfo,
            'strategy_used' => get_class($this->shippingStrategy),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->calculations[] = $result;
        
        echo "📋 Shipping calculation completed\n\n";
        return $result;
    }
    
    public function getCalculationHistory(): array {
        return $this->calculations;
    }
}

// ===============================================================================
// SORTING STRATEGY EXAMPLE
// ===============================================================================

/*
SORTING STRATEGY INTERFACE:
--------------------------
Different sorting algorithms এর জন্য interface।
*/
interface SortingStrategyInterface {
    public function sort(array $data): array;
    public function getName(): string;
    public function getComplexity(): string;
}

/*
BUBBLE SORT STRATEGY:
--------------------
Bubble sort algorithm implementation।
*/
class BubbleSortStrategy implements SortingStrategyInterface {
    public function sort(array $data): array {
        echo "🫧 [BUBBLE SORT] Sorting " . count($data) . " elements...\n";
        
        $array = $data;
        $n = count($array);
        $comparisons = 0;
        $swaps = 0;
        
        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = 0; $j < $n - $i - 1; $j++) {
                $comparisons++;
                if ($array[$j] > $array[$j + 1]) {
                    // Swap elements
                    $temp = $array[$j];
                    $array[$j] = $array[$j + 1];
                    $array[$j + 1] = $temp;
                    $swaps++;
                }
            }
        }
        
        echo "🫧 Comparisons: $comparisons, Swaps: $swaps\n";
        echo "🫧 Bubble sort completed\n";
        
        return $array;
    }
    
    public function getName(): string {
        return "Bubble Sort";
    }
    
    public function getComplexity(): string {
        return "O(n²)";
    }
}

/*
QUICK SORT STRATEGY:
-------------------
Quick sort algorithm implementation।
*/
class QuickSortStrategy implements SortingStrategyInterface {
    private $comparisons = 0;
    
    public function sort(array $data): array {
        echo "⚡ [QUICK SORT] Sorting " . count($data) . " elements...\n";
        
        $this->comparisons = 0;
        $result = $this->quickSort($data, 0, count($data) - 1);
        
        echo "⚡ Comparisons: {$this->comparisons}\n";
        echo "⚡ Quick sort completed\n";
        
        return $result;
    }
    
    private function quickSort(array $array, int $low, int $high): array {
        if ($low < $high) {
            $pi = $this->partition($array, $low, $high);
            
            $array = $this->quickSort($array, $low, $pi - 1);
            $array = $this->quickSort($array, $pi + 1, $high);
        }
        
        return $array;
    }
    
    private function partition(array &$array, int $low, int $high): int {
        $pivot = $array[$high];
        $i = $low - 1;
        
        for ($j = $low; $j <= $high - 1; $j++) {
            $this->comparisons++;
            if ($array[$j] < $pivot) {
                $i++;
                $temp = $array[$i];
                $array[$i] = $array[$j];
                $array[$j] = $temp;
            }
        }
        
        $temp = $array[$i + 1];
        $array[$i + 1] = $array[$high];
        $array[$high] = $temp;
        
        return $i + 1;
    }
    
    public function getName(): string {
        return "Quick Sort";
    }
    
    public function getComplexity(): string {
        return "O(n log n) average, O(n²) worst";
    }
}

/*
MERGE SORT STRATEGY:
-------------------
Merge sort algorithm implementation।
*/
class MergeSortStrategy implements SortingStrategyInterface {
    private $comparisons = 0;
    
    public function sort(array $data): array {
        echo "🔀 [MERGE SORT] Sorting " . count($data) . " elements...\n";
        
        $this->comparisons = 0;
        $result = $this->mergeSort($data);
        
        echo "🔀 Comparisons: {$this->comparisons}\n";
        echo "🔀 Merge sort completed\n";
        
        return $result;
    }
    
    private function mergeSort(array $array): array {
        if (count($array) <= 1) {
            return $array;
        }
        
        $mid = floor(count($array) / 2);
        $left = array_slice($array, 0, $mid);
        $right = array_slice($array, $mid);
        
        $left = $this->mergeSort($left);
        $right = $this->mergeSort($right);
        
        return $this->merge($left, $right);
    }
    
    private function merge(array $left, array $right): array {
        $result = [];
        $i = $j = 0;
        
        while ($i < count($left) && $j < count($right)) {
            $this->comparisons++;
            if ($left[$i] <= $right[$j]) {
                $result[] = $left[$i];
                $i++;
            } else {
                $result[] = $right[$j];
                $j++;
            }
        }
        
        while ($i < count($left)) {
            $result[] = $left[$i];
            $i++;
        }
        
        while ($j < count($right)) {
            $result[] = $right[$j];
            $j++;
        }
        
        return $result;
    }
    
    public function getName(): string {
        return "Merge Sort";
    }
    
    public function getComplexity(): string {
        return "O(n log n)";
    }
}

/*
ARRAY SORTER CONTEXT:
---------------------
Sorting strategies ব্যবহার করে array sort করার জন্য context।
*/
class ArraySorter {
    private $sortingStrategy;
    
    public function __construct(SortingStrategyInterface $sortingStrategy = null) {
        if ($sortingStrategy) {
            $this->sortingStrategy = $sortingStrategy;
            echo "📊 ArraySorter initialized with: " . $sortingStrategy->getName() . "\n";
        }
    }
    
    public function setSortingStrategy(SortingStrategyInterface $strategy): void {
        $this->sortingStrategy = $strategy;
        echo "🔄 Sorting strategy changed to: " . $strategy->getName() . "\n";
    }
    
    public function sortArray(array $data): array {
        if (!$this->sortingStrategy) {
            throw new Exception("No sorting strategy set!");
        }
        
        echo "\n📊 [ARRAY SORTER] Starting sort operation...\n";
        echo "📊 Strategy: " . $this->sortingStrategy->getName() . "\n";
        echo "📊 Complexity: " . $this->sortingStrategy->getComplexity() . "\n";
        echo "📊 Input: [" . implode(', ', array_slice($data, 0, 10)) . (count($data) > 10 ? '...' : '') . "]\n";
        
        $startTime = microtime(true);
        $result = $this->sortingStrategy->sort($data);
        $endTime = microtime(true);
        
        $processingTime = round(($endTime - $startTime) * 1000, 2);
        
        echo "📊 Output: [" . implode(', ', array_slice($result, 0, 10)) . (count($result) > 10 ? '...' : '') . "]\n";
        echo "📊 Processing time: {$processingTime}ms\n\n";
        
        return $result;
    }
}

// ===============================================================================
// COMPREHENSIVE DEMONSTRATION
// ===============================================================================

echo "\n" . str_repeat("=", 80) . "\n";
echo "🎭 STRATEGY PATTERN COMPREHENSIVE DEMONSTRATION\n";
echo str_repeat("=", 80) . "\n\n";

// ===============================================================================
// PAYMENT PROCESSING DEMONSTRATION
// ===============================================================================

echo "💳 PAYMENT PROCESSING DEMONSTRATION\n";
echo str_repeat("-", 40) . "\n";

// Create payment processor
$paymentProcessor = new PaymentProcessor();

// Test different payment strategies
$paymentMethods = [
    'credit_card' => [
        'card_number' => '4532123456789012',
        'cardholder_name' => 'আহমেদ করিম',
        'expiry_month' => '12',
        'expiry_year' => '2025',
        'cvv' => '123'
    ],
    'paypal' => [
        'email' => 'ahmed.karim@example.com'
    ],
    'bank_transfer' => [
        'account_number' => '1234567890',
        'routing_number' => '021000021',
        'account_holder' => 'আহমেদ করিম',
        'bank_name' => 'Chase Bank'
    ],
    'cryptocurrency' => [
        'currency' => 'BTC',
        'wallet_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa'
    ]
];

$testAmount = 150.00;

foreach ($paymentMethods as $method => $details) {
    echo "\n🎬 TESTING: " . strtoupper(str_replace('_', ' ', $method)) . "\n";
    echo str_repeat("-", 30) . "\n";
    
    // Create and set strategy
    $strategy = PaymentStrategyFactory::createStrategy($method);
    $paymentProcessor->setPaymentStrategy($strategy);
    
    // Calculate fees
    $fees = $paymentProcessor->calculateFees($testAmount);
    echo "💰 Estimated fees: $" . number_format($fees, 2) . "\n";
    
    // Validate details
    $isValid = $paymentProcessor->validatePaymentDetails($details);
    echo "✅ Details validation: " . ($isValid ? 'PASSED' : 'FAILED') . "\n";
    
    if ($isValid) {
        // Process payment
        $result = $paymentProcessor->processPayment($testAmount, $details);
        
        if ($result['status'] === 'success') {
            echo "🎉 Payment successful! Transaction ID: {$result['transaction_id']}\n";
        } else {
            echo "❌ Payment failed: {$result['message']}\n";
        }
    }
    
    echo "\n" . str_repeat("-", 30) . "\n";
}

// Display transaction history
echo "\n📊 TRANSACTION HISTORY:\n";
$history = $paymentProcessor->getTransactionHistory();
foreach ($history as $index => $transaction) {
    echo "Transaction " . ($index + 1) . ": {$transaction['payment_method']} - {$transaction['status']} - $" . number_format($transaction['amount'], 2) . "\n";
}

// ===============================================================================
// SHIPPING CALCULATION DEMONSTRATION
// ===============================================================================

echo "\n\n📦 SHIPPING CALCULATION DEMONSTRATION\n";
echo str_repeat("-", 40) . "\n";

$shippingCalculator = new ShippingCalculator();

$shippingMethods = [
    'express' => new ExpressShippingStrategy(),
    'standard' => new StandardShippingStrategy(),
    'economy' => new EconomyShippingStrategy()
];

$testWeight = 5.5; // pounds
$testDestination = 'CA'; // Canada

foreach ($shippingMethods as $method => $strategy) {
    echo "\n🎬 TESTING: " . strtoupper($method) . " SHIPPING\n";
    echo str_repeat("-", 30) . "\n";
    
    $shippingCalculator->setShippingStrategy($strategy);
    $result = $shippingCalculator->calculateShipping($testWeight, $testDestination);
    
    echo "📦 Cost: $" . number_format($result['cost'], 2) . "\n";
    echo "📦 Delivery: {$result['delivery_time']}\n";
    echo "📦 Tracking: {$result['tracking_info']['tracking_number']}\n";
    echo "📦 Real-time tracking: " . ($result['tracking_info']['real_time_tracking'] ? 'Yes' : 'No') . "\n";
    
    echo "\n" . str_repeat("-", 30) . "\n";
}

// ===============================================================================
// SORTING ALGORITHM DEMONSTRATION
// ===============================================================================

echo "\n\n📊 SORTING ALGORITHM DEMONSTRATION\n";
echo str_repeat("-", 40) . "\n";

$arraySorter = new ArraySorter();

$sortingStrategies = [
    new BubbleSortStrategy(),
    new QuickSortStrategy(),
    new MergeSortStrategy()
];

// Generate test data
$testData = [];
for ($i = 0; $i < 20; $i++) {
    $testData[] = rand(1, 100);
}

echo "🎲 Test Data: [" . implode(', ', $testData) . "]\n\n";

foreach ($sortingStrategies as $strategy) {
    echo "🎬 TESTING: " . strtoupper($strategy->getName()) . "\n";
    echo str_repeat("-", 30) . "\n";
    
    $arraySorter->setSortingStrategy($strategy);
    $sorted = $arraySorter->sortArray($testData);
    
    echo "✅ Sorted: [" . implode(', ', $sorted) . "]\n";
    echo "\n" . str_repeat("-", 30) . "\n";
}

// ===============================================================================
// PERFORMANCE COMPARISON
// ===============================================================================

echo "\n\n⚡ PERFORMANCE COMPARISON\n";
echo str_repeat("-", 40) . "\n";

// Payment processing performance
echo "💳 Payment Processing Performance:\n";

$performanceData = [];
$testAmount = 100.00;

foreach ($paymentMethods as $method => $details) {
    $strategy = PaymentStrategyFactory::createStrategy($method);
    $processor = new PaymentProcessor($strategy);
    
    $startTime = microtime(true);
    
    // Simulate multiple operations
    for ($i = 0; $i < 100; $i++) {
        $processor->calculateFees($testAmount);
        $processor->validatePaymentDetails($details);
    }
    
    $endTime = microtime(true);
    $processingTime = round(($endTime - $startTime) * 1000, 2);
    
    $performanceData[$method] = $processingTime;
    echo "   $method: {$processingTime}ms (100 operations)\n";
}

// Find fastest payment method
$fastestMethod = array_search(min($performanceData), $performanceData);
echo "🏆 Fastest payment method: $fastestMethod\n\n";

// Sorting performance comparison
echo "📊 Sorting Algorithm Performance (1000 elements):\n";

$largeTestData = [];
for ($i = 0; $i < 1000; $i++) {
    $largeTestData[] = rand(1, 1000);
}

$sortingPerformance = [];

foreach ($sortingStrategies as $strategy) {
    $sorter = new ArraySorter($strategy);
    
    $startTime = microtime(true);
    $sorter->sortArray($largeTestData);
    $endTime = microtime(true);
    
    $processingTime = round(($endTime - $startTime) * 1000, 2);
    $sortingPerformance[$strategy->getName()] = $processingTime;
    
    echo "   {$strategy->getName()}: {$processingTime}ms\n";
}

$fastestSort = array_search(min($sortingPerformance), $sortingPerformance);
echo "🏆 Fastest sorting algorithm: $fastestSort\n";

// ===============================================================================
// REAL-WORLD E-COMMERCE SIMULATION
// ===============================================================================

echo "\n\n🛒 REAL-WORLD E-COMMERCE SIMULATION\n";
echo str_repeat("-", 40) . "\n";

/*
E-COMMERCE ORDER CLASS:
----------------------
Complete e-commerce order যেখানে multiple strategies ব্যবহার হয়।
*/
class ECommerceOrder {
    private $orderId;
    private $items = [];
    private $totalAmount = 0;
    private $paymentProcessor;
    private $shippingCalculator;
    
    public function __construct(string $orderId) {
        $this->orderId = $orderId;
        $this->paymentProcessor = new PaymentProcessor();
        $this->shippingCalculator = new ShippingCalculator();
        echo "🛒 Order created: $orderId\n";
    }
    
    public function addItem(string $name, float $price, int $quantity = 1): void {
        $this->items[] = [
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'total' => $price * $quantity
        ];
        
        $this->totalAmount += $price * $quantity;
        echo "🛒 Added: $quantity x $name @ $" . number_format($price, 2) . "\n";
    }
    
    public function setPaymentMethod(string $method, array $details): void {
        $strategy = PaymentStrategyFactory::createStrategy($method);
        $this->paymentProcessor->setPaymentStrategy($strategy);
        
        // Validate payment details
        if (!$this->paymentProcessor->validatePaymentDetails($details)) {
            throw new Exception("Invalid payment details for $method");
        }
        
        echo "🛒 Payment method set: $method\n";
    }
    
    public function setShippingMethod(string $method): void {
        switch ($method) {
            case 'express':
                $strategy = new ExpressShippingStrategy();
                break;
            case 'standard':
                $strategy = new StandardShippingStrategy();
                break;
            case 'economy':
                $strategy = new EconomyShippingStrategy();
                break;
            default:
                throw new Exception("Unknown shipping method: $method");
        }
        
        $this->shippingCalculator->setShippingStrategy($strategy);
        echo "🛒 Shipping method set: $method\n";
    }
    
    public function processOrder(array $paymentDetails, float $weight, string $destination): array {
        echo "\n🛒 [ORDER PROCESSING] Processing order: {$this->orderId}\n";
        
        // Calculate shipping
        $shippingInfo = $this->shippingCalculator->calculateShipping($weight, $destination);
        $shippingCost = $shippingInfo['cost'];
        
        // Calculate total with shipping
        $grandTotal = $this->totalAmount + $shippingCost;
        
        echo "🛒 Items total: $" . number_format($this->totalAmount, 2) . "\n";
        echo "🛒 Shipping cost: $" . number_format($shippingCost, 2) . "\n";
        echo "🛒 Grand total: $" . number_format($grandTotal, 2) . "\n";
        
        // Process payment
        $paymentResult = $this->paymentProcessor->processPayment($grandTotal, $paymentDetails);
        
        if ($paymentResult['status'] !== 'success') {
            echo "❌ Order failed: Payment processing failed\n";
            return [
                'status' => 'failed',
                'message' => 'Payment processing failed',
                'order_id' => $this->orderId
            ];
        }
        
        echo "✅ Order processed successfully!\n";
        
        return [
            'status' => 'success',
            'order_id' => $this->orderId,
            'items' => $this->items,
            'subtotal' => $this->totalAmount,
            'shipping_cost' => $shippingCost,
            'grand_total' => $grandTotal,
            'payment_result' => $paymentResult,
            'shipping_info' => $shippingInfo,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// E-commerce simulation
echo "🛒 Creating sample order...\n";

$order = new ECommerceOrder('ORD-' . strtoupper(uniqid()));

// Add items to cart
$order->addItem('ল্যাপটপ', 899.99, 1);
$order->addItem('মাউস', 29.99, 2);
$order->addItem('কীবোর্ড', 79.99, 1);

// Set payment method (Credit Card)
$order->setPaymentMethod('credit_card', [
    'card_number' => '4532123456789012',
    'cardholder_name' => 'রহিম উদ্দিন',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123'
]);

// Set shipping method (Express)
$order->setShippingMethod('express');

// Process the order
$orderResult = $order->processOrder([
    'card_number' => '4532123456789012',
    'cardholder_name' => 'রহিম উদ্দিন',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123'
], 3.2, 'US');

echo "\n📋 ORDER SUMMARY:\n";
echo "Order ID: {$orderResult['order_id']}\n";
echo "Status: {$orderResult['status']}\n";
echo "Grand Total: $" . number_format($orderResult['grand_total'], 2) . "\n";
echo "Payment Method: {$orderResult['payment_result']['payment_method']}\n";
echo "Tracking Number: {$orderResult['shipping_info']['tracking_info']['tracking_number']}\n";

// ===============================================================================
// STRATEGY PATTERN COMPARISON WITH OTHER PATTERNS
// ===============================================================================

echo "\n\n🔄 STRATEGY PATTERN VS OTHER PATTERNS\n";
echo str_repeat("-", 40) . "\n";

echo "📋 STRATEGY vs STATE Pattern:\n\n";

echo "STRATEGY PATTERN:\n";
echo "✅ Different algorithms for same problem\n";
echo "✅ Behavior is independent of context state\n";
echo "✅ Client chooses strategy explicitly\n";
echo "✅ Strategies don't know about each other\n\n";

echo "STATE PATTERN:\n";
echo "✅ Different behaviors based on internal state\n";
echo "✅ Context changes state automatically\n";
echo "✅ States may know about other states\n";
echo "✅ Behavior changes as state changes\n\n";

echo "📋 STRATEGY vs COMMAND Pattern:\n\n";

echo "STRATEGY PATTERN:\n";
echo "✅ Focuses on HOW to do something\n";
echo "✅ Encapsulates algorithms\n";
echo "✅ Usually stateless\n";
echo "✅ Called immediately\n\n";

echo "COMMAND PATTERN:\n";
echo "✅ Focuses on WHAT to do\n";
echo "✅ Encapsulates requests\n";
echo "✅ Can store state\n";
echo "✅ Can be queued, logged, undone\n\n";

echo "📋 STRATEGY vs TEMPLATE METHOD Pattern:\n\n";

echo "STRATEGY PATTERN:\n";
echo "✅ Uses composition (has-a relationship)\n";
echo "✅ Changes entire algorithm\n";
echo "✅ Runtime algorithm selection\n";
echo "✅ Favor composition over inheritance\n\n";

echo "TEMPLATE METHOD PATTERN:\n";
echo "✅ Uses inheritance (is-a relationship)\n";
echo "✅ Changes parts of algorithm\n";
echo "✅ Compile-time algorithm definition\n";
echo "✅ Defines skeleton, subclasses fill details\n";

// ===============================================================================
// BEST PRACTICES AND ANTI-PATTERNS
// ===============================================================================

echo "\n\n💡 BEST PRACTICES AND ANTI-PATTERNS\n";
echo str_repeat("-", 40) . "\n";

echo "✅ BEST PRACTICES:\n\n";

echo "1. INTERFACE SEGREGATION:\n";
echo "   - Keep strategy interfaces focused and cohesive\n";
echo "   - Don't force strategies to implement unused methods\n";
echo "   - Use multiple small interfaces instead of one large interface\n\n";

echo "2. STRATEGY FACTORY:\n";
echo "   - Use factory pattern to create strategies\n";
echo "   - Centralize strategy creation logic\n";
echo "   - Make strategy selection easier\n\n";

echo "3. NULL OBJECT PATTERN:\n";
echo "   - Provide default 'do nothing' strategy\n";
echo "   - Avoid null checks in context\n";
echo "   - Graceful handling when no strategy is set\n\n";

echo "4. CONFIGURATION-DRIVEN STRATEGY SELECTION:\n";
echo "   - Use configuration files or databases\n";
echo "   - Allow runtime strategy changes\n";
echo "   - Support A/B testing scenarios\n\n";

echo "❌ ANTI-PATTERNS TO AVOID:\n\n";

echo "1. TOO MANY STRATEGIES:\n";
echo "   - Don't create strategies for minor variations\n";
echo "   - Consider parameterized strategies instead\n";
echo "   - Group similar strategies into one with configuration\n\n";

echo "2. STRATEGY WITH COMPLEX STATE:\n";
echo "   - Keep strategies stateless when possible\n";
echo "   - Don't share mutable state between strategies\n";
echo "   - Consider State pattern for stateful behaviors\n\n";

echo "3. EXPOSING STRATEGY INTERNALS:\n";
echo "   - Don't let context know about strategy implementation details\n";
echo "   - Keep strategy interface abstract\n";
echo "   - Avoid tight coupling between context and strategies\n\n";

echo "4. OVERUSING STRATEGY PATTERN:\n";
echo "   - Don't use for simple if/else scenarios\n";
echo "   - Consider simple conditionals for 2-3 options\n";
echo "   - Use when you have multiple complex algorithms\n\n";

// ===============================================================================
// ADVANCED STRATEGY PATTERNS
// ===============================================================================

echo "🚀 ADVANCED STRATEGY PATTERNS\n";
echo str_repeat("-", 40) . "\n";

/*
PARAMETERIZED STRATEGY:
----------------------
Strategy যা configuration parameters accept করে।
*/
class ParameterizedDiscountStrategy implements PaymentStrategyInterface {
    private $discountPercentage;
    private $baseStrategy;
    
    public function __construct(PaymentStrategyInterface $baseStrategy, float $discountPercentage) {
        $this->baseStrategy = $baseStrategy;
        $this->discountPercentage = $discountPercentage;
        echo "🎯 ParameterizedDiscountStrategy created with {$discountPercentage}% discount\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        // Apply discount
        $discountAmount = $amount * ($this->discountPercentage / 100);
        $discountedAmount = $amount - $discountAmount;
        
        echo "🎯 [DISCOUNT STRATEGY] Original: $" . number_format($amount, 2) . "\n";
        echo "🎯 Discount ({$this->discountPercentage}%): -$" . number_format($discountAmount, 2) . "\n";
        echo "🎯 Final amount: $" . number_format($discountedAmount, 2) . "\n";
        
        // Delegate to base strategy
        $result = $this->baseStrategy->processPayment($discountedAmount, $details);
        
        // Add discount information
        if ($result['status'] === 'success') {
            $result['original_amount'] = $amount;
            $result['discount_applied'] = $discountAmount;
            $result['discount_percentage'] = $this->discountPercentage;
        }
        
        return $result;
    }
    
    public function validate(array $details): bool {
        return $this->baseStrategy->validate($details);
    }
    
    public function getFees(float $amount): float {
        $discountedAmount = $amount * (1 - $this->discountPercentage / 100);
        return $this->baseStrategy->getFees($discountedAmount);
    }
}

/*
COMPOSITE STRATEGY:
------------------
Multiple strategies কে combine করে।
*/
class CompositePaymentStrategy implements PaymentStrategyInterface {
    private $strategies = [];
    private $weights = [];
    
    public function addStrategy(PaymentStrategyInterface $strategy, float $weight = 1.0): void {
        $this->strategies[] = $strategy;
        $this->weights[] = $weight;
        echo "🔗 Added strategy to composite: " . get_class($strategy) . " (weight: $weight)\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        echo "🔗 [COMPOSITE STRATEGY] Processing with " . count($this->strategies) . " strategies\n";
        
        if (empty($this->strategies)) {
            return [
                'status' => 'failed',
                'message' => 'No strategies configured'
            ];
        }
        
        // Try each strategy based on weights
        $totalWeight = array_sum($this->weights);
        $random = mt_rand() / mt_getrandmax() * $totalWeight;
        
        $currentWeight = 0;
        foreach ($this->strategies as $index => $strategy) {
            $currentWeight += $this->weights[$index];
            if ($random <= $currentWeight) {
                echo "🔗 Selected strategy: " . get_class($strategy) . "\n";
                return $strategy->processPayment($amount, $details);
            }
        }
        
        // Fallback to first strategy
        return $this->strategies[0]->processPayment($amount, $details);
    }
    
    public function validate(array $details): bool {
        // All strategies must validate successfully
        foreach ($this->strategies as $strategy) {
            if (!$strategy->validate($details)) {
                return false;
            }
        }
        return true;
    }
    
    public function getFees(float $amount): float {
        if (empty($this->strategies)) {
            return 0;
        }
        
        // Return average fees
        $totalFees = 0;
        foreach ($this->strategies as $strategy) {
            $totalFees += $strategy->getFees($amount);
        }
        
        return $totalFees / count($this->strategies);
    }
}

/*
CACHING STRATEGY DECORATOR:
--------------------------
Strategy results cache করে performance improve করে।
*/
class CachingStrategyDecorator implements PaymentStrategyInterface {
    private $baseStrategy;
    private $cache = [];
    private $cacheHits = 0;
    private $cacheMisses = 0;
    
    public function __construct(PaymentStrategyInterface $baseStrategy) {
        $this->baseStrategy = $baseStrategy;
        echo "💾 CachingStrategyDecorator created for: " . get_class($baseStrategy) . "\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        // Create cache key
        $cacheKey = $this->generateCacheKey($amount, $details);
        
        // Check cache
        if (isset($this->cache[$cacheKey])) {
            $this->cacheHits++;
            echo "💾 [CACHE HIT] Returning cached result\n";
            return $this->cache[$cacheKey];
        }
        
        // Process payment
        $this->cacheMisses++;
        echo "💾 [CACHE MISS] Processing payment\n";
        $result = $this->baseStrategy->processPayment($amount, $details);
        
        // Cache successful results
        if ($result['status'] === 'success') {
            $this->cache[$cacheKey] = $result;
        }
        
        return $result;
    }
    
    public function validate(array $details): bool {
        return $this->baseStrategy->validate($details);
    }
    
    public function getFees(float $amount): float {
        return $this->baseStrategy->getFees($amount);
    }
    
    private function generateCacheKey(float $amount, array $details): string {
        return md5(serialize([$amount, $details]));
    }
    
    public function getCacheStats(): array {
        return [
            'hits' => $this->cacheHits,
            'misses' => $this->cacheMisses,
            'hit_ratio' => $this->cacheHits / max(1, $this->cacheHits + $this->cacheMisses)
        ];
    }
}

// ===============================================================================
// ADVANCED STRATEGIES DEMONSTRATION
// ===============================================================================

echo "\n🎯 ADVANCED STRATEGIES DEMONSTRATION\n";
echo str_repeat("-", 40) . "\n";

// Parameterized Strategy Demo
echo "🎯 PARAMETERIZED STRATEGY (25% Discount):\n";
echo str_repeat("-", 30) . "\n";

$baseStrategy = new CreditCardPaymentStrategy();
$discountStrategy = new ParameterizedDiscountStrategy($baseStrategy, 25.0);

$processor = new PaymentProcessor($discountStrategy);
$result = $processor->processPayment(200.00, [
    'card_number' => '4532123456789012',
    'cardholder_name' => 'Discount Customer',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123'
]);

echo "💰 Savings: $" . number_format($result['discount_applied'] ?? 0, 2) . "\n\n";

// Composite Strategy Demo
echo "🔗 COMPOSITE STRATEGY (Multiple Payment Options):\n";
echo str_repeat("-", 30) . "\n";

$compositeStrategy = new CompositePaymentStrategy();
$compositeStrategy->addStrategy(new CreditCardPaymentStrategy(), 0.6); // 60% weight
$compositeStrategy->addStrategy(new PayPalPaymentStrategy(), 0.3);      // 30% weight
$compositeStrategy->addStrategy(new BankTransferPaymentStrategy(), 0.1); // 10% weight

$processor->setPaymentStrategy($compositeStrategy);

// Test multiple times to see different strategies being selected
for ($i = 1; $i <= 3; $i++) {
    echo "\n🎲 Random selection attempt #$i:\n";
    $result = $processor->validatePaymentDetails([
        'card_number' => '4532123456789012',
        'cardholder_name' => 'Test User',
        'expiry_month' => '12',
        'expiry_year' => '2025',
        'cvv' => '123',
        'email' => 'test@example.com',
        'account_number' => '1234567890',
        'routing_number' => '021000021',
        'account_holder' => 'Test User',
        'bank_name' => 'Test Bank'
    ]);
}

// Caching Strategy Demo
echo "\n\n💾 CACHING STRATEGY DEMONSTRATION:\n";
echo str_repeat("-", 30) . "\n";

$cachingStrategy = new CachingStrategyDecorator(new CreditCardPaymentStrategy());
$processor->setPaymentStrategy($cachingStrategy);

$testDetails = [
    'card_number' => '4532123456789012',
    'cardholder_name' => 'Cache Test',
    'expiry_month' => '12',
    'expiry_year' => '2025',
    'cvv' => '123'
];

// First call - cache miss
echo "First call (should be cache miss):\n";
$processor->processPayment(100.00, $testDetails);

// Second call - cache hit
echo "\nSecond call (should be cache hit):\n";
$processor->processPayment(100.00, $testDetails);

// Display cache statistics
$cacheStats = $cachingStrategy->getCacheStats();
echo "\n💾 Cache Statistics:\n";
echo "   Hits: {$cacheStats['hits']}\n";
echo "   Misses: {$cacheStats['misses']}\n";
echo "   Hit Ratio: " . number_format($cacheStats['hit_ratio'] * 100, 1) . "%\n";

// ===============================================================================
// LARAVEL-STYLE STRATEGY IMPLEMENTATION
// ===============================================================================

echo "\n\n🔥 LARAVEL-STYLE STRATEGY IMPLEMENTATION\n";
echo str_repeat("-", 40) . "\n";

/*
LARAVEL-STYLE MANAGER:
---------------------
Laravel এর Manager pattern এর মতো strategy management।
*/
class PaymentManager {
    private $strategies = [];
    private $defaultStrategy = 'credit_card';
    
    public function __construct() {
        echo "🔥 PaymentManager initialized (Laravel style)\n";
        $this->registerDefaultStrategies();
    }
    
    private function registerDefaultStrategies(): void {
        $this->extend('credit_card', function() {
            return new CreditCardPaymentStrategy();
        });
        
        $this->extend('paypal', function() {
            return new PayPalPaymentStrategy();
        });
        
        $this->extend('bank_transfer', function() {
            return new BankTransferPaymentStrategy();
        });
        
        $this->extend('crypto', function() {
            return new CryptocurrencyPaymentStrategy();
        });
    }
    
    public function extend(string $name, callable $callback): void {
        $this->strategies[$name] = $callback;
        echo "🔥 Extended with strategy: $name\n";
    }
    
    public function driver(string $name = null): PaymentStrategyInterface {
        $name = $name ?: $this->defaultStrategy;
        
        if (!isset($this->strategies[$name])) {
            throw new InvalidArgumentException("Driver [$name] not supported.");
        }
        
        echo "🔥 Creating driver: $name\n";
        return $this->strategies[$name]();
    }
    
    public function getDefaultDriver(): string {
        return $this->defaultStrategy;
    }
    
    public function setDefaultDriver(string $name): void {
        $this->defaultStrategy = $name;
        echo "🔥 Default driver set to: $name\n";
    }
    
    // Magic method to call driver methods directly
    public function __call(string $method, array $parameters) {
        return $this->driver()->$method(...$parameters);
    }
}

// Laravel-style usage demonstration
echo "🔥 Laravel-style Manager Demo:\n";

$paymentManager = new PaymentManager();

// Use default driver (credit_card)
echo "\n🔥 Using default driver:\n";
$fees = $paymentManager->getFees(150.00);
echo "🔥 Fees with default driver: $" . number_format($fees, 2) . "\n";

// Switch to PayPal driver
echo "\n🔥 Switching to PayPal driver:\n";
$paypalDriver = $paymentManager->driver('paypal');
$fees = $paypalDriver->getFees(150.00);
echo "🔥 Fees with PayPal: $" . number_format($fees, 2) . "\n";

// Set new default
$paymentManager->setDefaultDriver('paypal');

// Add custom strategy
$paymentManager->extend('custom', function() {
    echo "🔥 Creating custom payment strategy\n";
    return new class implements PaymentStrategyInterface {
        public function processPayment(float $amount, array $details): array {
            return [
                'status' => 'success',
                'message' => 'Custom payment processed',
                'transaction_id' => 'CUSTOM_' . uniqid(),
                'amount' => $amount
            ];
        }
        
        public function validate(array $details): bool {
            return true;
        }
        
        public function getFees(float $amount): float {
            return 0; // No fees for custom strategy
        }
    };
});

// Use custom strategy
echo "\n🔥 Using custom strategy:\n";
$customDriver = $paymentManager->driver('custom');
$customFees = $customDriver->getFees(150.00);
echo "🔥 Fees with custom driver: $" . number_format($customFees, 2) . "\n";

// ===============================================================================
// TESTING STRATEGIES
// ===============================================================================

echo "\n\n🧪 TESTING STRATEGIES\n";
echo str_repeat("-", 40) . "\n";

/*
MOCK PAYMENT STRATEGY:
---------------------
Testing এর জন্য mock strategy।
*/
class MockPaymentStrategy implements PaymentStrategyInterface {
    private $shouldSucceed;
    private $mockFees;
    
    public function __construct(bool $shouldSucceed = true, float $mockFees = 5.00) {
        $this->shouldSucceed = $shouldSucceed;
        $this->mockFees = $mockFees;
        echo "🧪 MockPaymentStrategy created (Success: " . ($shouldSucceed ? 'Yes' : 'No') . ")\n";
    }
    
    public function processPayment(float $amount, array $details): array {
        echo "🧪 [MOCK PAYMENT] Processing $" . number_format($amount, 2) . "\n";
        
        if ($this->shouldSucceed) {
            return [
                'status' => 'success',
                'message' => 'Mock payment successful',
                'transaction_id' => 'MOCK_' . uniqid(),
                'amount' => $amount,
                'fees' => $this->mockFees,
                'payment_method' => 'mock'
            ];
        } else {
            return [
                'status' => 'failed',
                'message' => 'Mock payment failed',
                'transaction_id' => null
            ];
        }
    }
    
    public function validate(array $details): bool {
        echo "🧪 Mock validation always returns true\n";
        return true;
    }
    
    public function getFees(float $amount): float {
        return $this->mockFees;
    }
}

/*
SIMPLE TEST FRAMEWORK:
---------------------
Strategy testing এর জন্য simple test framework।
*/
class StrategyTester {
    private $testResults = [];
    
    public function testPaymentStrategy(PaymentStrategyInterface $strategy, string $strategyName): void {
        echo "\n🧪 Testing: $strategyName\n";
        echo str_repeat("-", 20) . "\n";
        
        $testCases = [
            'fees_calculation' => $this->testFeesCalculation($strategy),
            'validation' => $this->testValidation($strategy),
            'payment_processing' => $this->testPaymentProcessing($strategy)
        ];
        
        $this->testResults[$strategyName] = $testCases;
        
        $passed = array_filter($testCases);
        $total = count($testCases);
        $passedCount = count($passed);
        
        echo "🧪 Results: $passedCount/$total tests passed\n";
        
        if ($passedCount === $total) {
            echo "✅ All tests passed for $strategyName\n";
        } else {
            echo "❌ Some tests failed for $strategyName\n";
        }
    }
    
    private function testFeesCalculation(PaymentStrategyInterface $strategy): bool {
        try {
            $fees = $strategy->getFees(100.00);
            $result = is_numeric($fees) && $fees >= 0;
            echo "🧪 Fees calculation: " . ($result ? "PASS" : "FAIL") . " ($fees)\n";
            return $result;
        } catch (Exception $e) {
            echo "🧪 Fees calculation: FAIL (Exception: {$e->getMessage()})\n";
            return false;
        }
    }
    
    private function testValidation(PaymentStrategyInterface $strategy): bool {
        try {
            // Test with valid and invalid data
            $validData = ['test' => 'data'];
            $invalidData = [];
            
            $validResult = $strategy->validate($validData);
            $invalidResult = $strategy->validate($invalidData);
            
            // For mock strategy, both will return true
            $result = is_bool($validResult) && is_bool($invalidResult);
            echo "🧪 Validation: " . ($result ? "PASS" : "FAIL") . "\n";
            return $result;
        } catch (Exception $e) {
            echo "🧪 Validation: FAIL (Exception: {$e->getMessage()})\n";
            return false;
        }
    }
    
    private function testPaymentProcessing(PaymentStrategyInterface $strategy): bool {
        try {
            $result = $strategy->processPayment(50.00, ['test' => 'data']);
            $hasStatus = isset($result['status']);
            $hasMessage = isset($result['message']);
            
            $passed = $hasStatus && $hasMessage;
            echo "🧪 Payment processing: " . ($passed ? "PASS" : "FAIL") . "\n";
            return $passed;
        } catch (Exception $e) {
            echo "🧪 Payment processing: FAIL (Exception: {$e->getMessage()})\n";
            return false;
        }
    }
    
    public function generateTestReport(): void {
        echo "\n📊 TEST REPORT\n";
        echo str_repeat("=", 40) . "\n";
        
        foreach ($this->testResults as $strategyName => $tests) {
            $passed = array_filter($tests);
            $total = count($tests);
            $passedCount = count($passed);
            $percentage = round(($passedCount / $total) * 100, 1);
            
            echo "$strategyName: $passedCount/$total ($percentage%)\n";
        }
    }
}

// Run strategy tests
$tester = new StrategyTester();

// Test different strategies
$testStrategies = [
    'Mock Success' => new MockPaymentStrategy(true),
    'Mock Failure' => new MockPaymentStrategy(false),
    'Credit Card' => new CreditCardPaymentStrategy(),
    'PayPal' => new PayPalPaymentStrategy()
];

foreach ($testStrategies as $name => $strategy) {
    $tester->testPaymentStrategy($strategy, $name);
}

$tester->generateTestReport();

// ===============================================================================
// SUMMARY AND CONCLUSION
// ===============================================================================

echo "\n\n📚 SUMMARY AND CONCLUSION\n";
echo str_repeat("=", 80) . "\n";

echo "
🎯 STRATEGY PATTERN KEY LEARNINGS:
---------------------------------

1. CORE CONCEPTS:
   ✅ Family of algorithms encapsulated in separate classes
   ✅ Algorithms are interchangeable at runtime
   ✅ Context class uses strategies through common interface
   ✅ Client can choose which strategy to use

2. IMPLEMENTATION COMPONENTS:
   ✅ Strategy Interface - Common contract for all algorithms
   ✅ Concrete Strategies - Specific algorithm implementations  
   ✅ Context Class - Uses strategies to perform operations
   ✅ Client Code - Selects and configures strategies

3. REAL-WORLD APPLICATIONS:
   💳 Payment Processing - Credit Card, PayPal, Bank Transfer
   📦 Shipping Methods - Express, Standard, Economy
   📊 Sorting Algorithms - Quick Sort, Merge Sort, Bubble Sort
   🔐 Encryption Methods - AES, DES, RSA
   💰 Pricing Strategies - Regular, Discount, Premium
   📱 Notification Methods - Email, SMS, Push Notifications

4. ADVANCED TECHNIQUES:
   🎯 Parameterized Strategies - Configurable algorithms
   🔗 Composite Strategies - Multiple strategies combined
   💾 Caching Decorators - Performance optimization
   🔥 Manager Pattern - Laravel-style strategy management
   🧪 Mock Strategies - Testing and development

5. ADVANTAGES:
   ✅ Open/Closed Principle - Easy to add new strategies
   ✅ Runtime Algorithm Selection - Dynamic behavior change
   ✅ Eliminates Conditional Statements - Cleaner code
   ✅ Easy Testing - Each strategy tested independently
   ✅ Single Responsibility - Each strategy has one purpose
   ✅ Code Reusability - Strategies can be reused

6. CHALLENGES:
   ⚠️ Increased number of classes
   ⚠️ Client must know about different strategies
   ⚠️ Communication overhead between context and strategy
   ⚠️ Potential over-engineering for simple scenarios

7. WHEN TO USE:
   ✅ Multiple ways to perform the same task
   ✅ Need to switch algorithms at runtime
   ✅ Want to eliminate large conditional statements
   ✅ Algorithms are complex and deserve their own classes
   ✅ Need to support A/B testing of different approaches

8. WHEN NOT TO USE:
   ❌ Only 1-2 simple algorithms exist
   ❌ Algorithms rarely change
   ❌ Performance is critical and strategy switching has overhead
   ❌ The context and strategies are tightly coupled

9. COMPARISON WITH OTHER PATTERNS:
   🔄 vs State Pattern - Strategy focuses on algorithms, State on behavior changes
   📝 vs Command Pattern - Strategy is about how, Command is about what
   🏗️ vs Template Method - Strategy uses composition, Template Method uses inheritance

10. BEST PRACTICES:
    💡 Keep strategies stateless when possible
    💡 Use factory pattern for strategy creation
    💡 Provide sensible defaults
    💡 Consider configuration-driven strategy selection
    💡 Use dependency injection for strategy assignment
    💡 Cache strategy instances when appropriate

🎊 CONCLUSION:
-------------
Strategy Pattern হলো একটি powerful behavioral pattern যা algorithm 
encapsulation এবং runtime selection এর জন্য perfect। Modern applications 
এ যেখানে flexibility এবং maintainability important, সেখানে এই pattern 
অত্যন্ত কার্যকর।

Payment gateways, shipping calculators, sorting algorithms, এবং অন্যান্য 
configurable behaviors implement করার জন্য এই pattern ব্যাপকভাবে ব্যবহৃত হয়।

🚀 NEXT STEPS:
-------------
1. Laravel Payment Systems explore করুন
2. E-commerce platforms এ Strategy Pattern এর usage দেখুন
3. Microservices এ Strategy Pattern implementation করুন
4. A/B testing frameworks এ Strategy Pattern ব্যবহার করুন
5. Machine Learning algorithms এ Strategy Pattern apply করুন
";

echo "\n🎉 STRATEGY PATTERN TUTORIAL COMPLETE! 🎉\n";
echo str_repeat("=", 80) . "\n";

/*
===============================================================================
FINAL IMPLEMENTATION CHECKLIST:
===============================================================================

✅ Strategy Interface defined with common contract
✅ Multiple Concrete Strategies implemented:
   - Payment strategies (Credit Card, PayPal, Bank Transfer, Crypto)
   - Shipping strategies (Express, Standard, Economy)  
   - Sorting strategies (Bubble Sort, Quick Sort, Merge Sort)
✅ Context classes (PaymentProcessor, ShippingCalculator, ArraySorter)
✅ Strategy Factory for centralized creation
✅ Advanced patterns:
   - Parameterized strategies
   - Composite strategies
   - Caching decorators
   - Laravel-style manager
✅ Real-world e-commerce simulation
✅ Comprehensive testing framework
✅ Performance comparisons
✅ Best practices and anti-patterns
✅ Pattern comparisons
✅ Complete documentation and examples

এই implementation দিয়ে আপনি production-ready Strategy Pattern 
system তৈরি করতে পারবেন যা scalable এবং maintainable।
===============================================================================
*/
