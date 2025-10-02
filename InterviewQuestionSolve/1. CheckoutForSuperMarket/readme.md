# Supermarket Checkout Kata

A PHP implementation of the supermarket checkout kata with comprehensive test coverage using PHPUnit.

## Problem Statement

Implement a checkout system that calculates the total price of items with support for promotional pricing:

- Items are identified by SKU (single letters: A, B, C, D)
- Each item has a unit price
- Some items have promotional pricing (bulk discounts or "buy X get Y free")
- The checkout accepts items in any order
- Pricing rules can be configured dynamically

## Current Pricing Rules

| Item | Unit Price | Promotion           |
|------|-----------|---------------------|
| A    | 50¢       | 3 for 130¢          |
| B    | 30¢       | 2 for 45¢           |
| C    | 20¢       | Buy 2, get 1 free   |
| D    | 15¢       | No promotion        |

## Features

- ✅ **Flexible Promotion System**: Supports two types of promotions
  - Bulk discount (e.g., "3 for 130")
  - Buy X Get Y Free (e.g., "Buy 2 get 1 free")
- ✅ **Order Independence**: Items can be scanned in any order
- ✅ **Dynamic Pricing Rules**: Pricing rules passed as parameters
- ✅ **Clean Architecture**: Object-oriented design with single responsibility
- ✅ **100% Test Coverage**: 21 comprehensive test cases

## Installation

```bash
# Clone the repository
git clone <your-repo-url>
cd checkout-kata

# Install dependencies
composer install
```

## Project Structure

```
.
├── src/
│   └── App/
│       └── Checkout.php       # Main checkout implementation
├── tests/
│   └── CheckoutTest.php       # PHPUnit test suite
├── composer.json
├── phpunit.xml
└── README.md
```

## Usage

```php
use App\Checkout;

$checkout = new Checkout();
$pricingRules = $checkout->getCurrentPricingRules();

// Calculate price for a cart
$total = $checkout->calculatePriceOfCart('AABBCABD', $pricingRules);
echo $total; // Output: 260

// With custom pricing rules
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
$total = $checkout->calculatePriceOfCart('XYY', $customRules);
echo $total; // Output: 400
```

## Running Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run with testdox format (readable output)
./vendor/bin/phpunit --testdox

# Run with colors
./vendor/bin/phpunit --testdox --colors=always
```

## Test Results

```
PHPUnit 11.5.42 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.2.29

Checkout
 ✔ Empty cart
 ✔ Single item a
 ✔ Single item b
 ✔ Single item c
 ✔ Single item d
 ✔ Two items a
 ✔ Three items a with promotion
 ✔ Four items a
 ✔ Six items a
 ✔ Two items b with promotion
 ✔ Three items b
 ✔ Two items c
 ✔ Three items c buy two get one free
 ✔ Four items c
 ✔ Six items c
 ✔ Mixed cart b a b
 ✔ Example cart
 ✔ Complex mixed cart
 ✔ Order does not matter
 ✔ Unknown s k u
 ✔ Custom pricing rules

OK (21 tests, 23 assertions)
```

## Example Calculations

### Example 1: Mixed Cart
**Cart**: `BAB`
- B × 1 = 30¢
- A × 1 = 50¢
- B × 1 = 30¢
- **Promotion Applied**: 2 B's = 45¢
- **Total**: 45¢ + 50¢ = **95¢**

### Example 2: Full Cart
**Cart**: `AABBCABD` (4 A's, 2 B's, 1 C, 1 D)
- A × 4: (3 for 130¢) + 1 for 50¢ = 180¢
- B × 2: (2 for 45¢) = 45¢
- C × 1: 20¢
- D × 1: 15¢
- **Total**: **260¢**

### Example 3: Buy X Get Y Free
**Cart**: `CCC`
- C × 3: Buy 2 get 1 free
- **Pay for**: 2 items = 40¢
- **Total**: **40¢**

## Requirements

- PHP 8.2+
- Composer
- PHPUnit 11.5+

## Design Decisions

1. **Separation of Concerns**: Cart parsing, pricing calculation, and promotion logic are separated into distinct methods
2. **Strategy Pattern**: Different promotion types handled through polymorphic behavior
3. **Immutability**: Pricing rules are passed as parameters, making the system flexible and testable
4. **Type Safety**: Strong typing with PHP 8.2+ type declarations
5. **Clean Code**: Descriptive method names and single responsibility principle

## Author

**Shuvo**

## License

This project is open source and available under the MIT License.
