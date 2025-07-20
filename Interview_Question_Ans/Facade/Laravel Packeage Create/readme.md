# Laravel Calculator Package

A simple and elegant calculator package for Laravel with Facade support.

## Installation

You can install the package via composer:

```bash
composer require yourvendor/laravel-calculator
```

## Configuration

Publish the config file with:

```bash
php artisan vendor:publish --tag="calculator-config"
```

This will publish a `calculator.php` config file to your config directory.

## Usage

### Using the Facade

```php
use Calculator;

// Basic operations
$sum = Calculator::add(5, 3);          // 8
$difference = Calculator::subtract(10, 4);  // 6
$product = Calculator::multiply(4, 7);     // 28
$quotient = Calculator::divide(20, 4);     // 5

// Advanced operations
$power = Calculator::power(2, 3);          // 8
$sqrt = Calculator::sqrt(16);              // 4
$percentage = Calculator::percentage(200, 15); // 30
$modulo = Calculator::modulo(10, 3);       // 1
```

### Method Chaining

```php
$result = Calculator::reset()
    ->add(10)
    ->add(5)
    ->subtract(3)
    ->getResult(); // 12
```

### Using Without Facade

```php
use YourVendor\Calculator\CalculatorService;

$calculator = new CalculatorService();
$result = $calculator->add(5, 3); // 8
```

### Working with History

```php
// Perform some calculations
Calculator::add(5, 3);
Calculator::multiply(4, 2);
Calculator::divide(10, 2);

// Get history
$history = Calculator::getHistory();
/*
[
    [
        'operation' => 'add',
        'a' => 5,
        'b' => 3,
        'result' => 8,
        'timestamp' => '2024-01-01 12:00:00'
    ],
    ...
]
*/

// Clear history
Calculator::clearHistory();
```

### Setting Precision

```php
Calculator::setPrecision(4);
$result = Calculator::divide(10, 3); // 3.3333
```

## Configuration Options

```php
// config/calculator.php

return [
    'precision' => 2,           // Number of decimal places
    'enable_history' => true,   // Enable operation history
    'history_limit' => 100,     // Maximum history entries
];
```

## Error Handling

The calculator handles common errors:

```php
try {
    $result = Calculator::divide(10, 0);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage(); // "Division by zero is not allowed"
}

try {
    $result = Calculator::sqrt(-4);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage(); // "Cannot calculate square root of negative number"
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email your.email@example.com instead of using the issue tracker.

## Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
