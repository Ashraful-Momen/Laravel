# Prototype Pattern in PHP - Simple Guide

## Definition
**Clone object, not copy the object.** Creates new instances by copying existing instances.

## Simple Concept
Instead of creating objects from scratch, copy an existing object and modify it.

## Real-life Use Cases
- Email templates
- Product catalog items  
- Game characters
- Configuration presets

## Code Example

```php
<?php
// Simple Car example
class Car 
{
    public $brand;
    public $model;
    public $color;
    public $price;
    
    public function __construct($brand, $model, $color, $price) 
    {
        $this->brand = $brand;
        $this->model = $model;
        $this->color = $color;
        $this->price = $price;
    }
    
    public function getInfo() 
    {
        return "{$this->brand} {$this->model} - {$this->color} - ${$this->price}";
    }
    
    // This is the PROTOTYPE method - clone the object
    public function createVariant() 
    {
        return clone $this;
    }
}

// Usage - The Power of Prototype Pattern
echo "=== WITHOUT Prototype Pattern ===\n";
// Creating each car from scratch (tedious!)
$car1 = new Car("Toyota", "Camry", "Red", 25000);
$car2 = new Car("Toyota", "Camry", "Blue", 25000);
$car3 = new Car("Toyota", "Camry", "Black", 25000);

echo $car1->getInfo() . "\n";
echo $car2->getInfo() . "\n";
echo $car3->getInfo() . "\n";

echo "\n=== WITH Prototype Pattern ===\n";
// Create one base car (prototype)
$baseCar = new Car("Toyota", "Camry", "White", 25000);

// Clone it and just change what's different
$redCar = $baseCar->createVariant();
$redCar->color = "Red";

$blueCar = $baseCar->createVariant();
$blueCar->color = "Blue";

$expensiveCar = $baseCar->createVariant();
$expensiveCar->color = "Gold";
$expensiveCar->price = 35000;

echo "Base: " . $baseCar->getInfo() . "\n";
echo "Red: " . $redCar->getInfo() . "\n";
echo "Blue: " . $blueCar->getInfo() . "\n";
echo "Gold: " . $expensiveCar->getInfo() . "\n";

echo "\n=== Real-World Example: Email Template ===\n";

class EmailTemplate 
{
    public $subject;
    public $body;
    public $footer;
    
    public function __construct($subject, $body, $footer) 
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->footer = $footer;
    }
    
    public function createPersonalizedEmail() 
    {
        return clone $this; // PROTOTYPE!
    }
    
    public function show() 
    {
        return "Subject: {$this->subject}\nBody: {$this->body}\nFooter: {$this->footer}";
    }
}

// Create one base template
$welcomeTemplate = new EmailTemplate(
    "Welcome to Our Service!", 
    "Dear Customer, welcome to our platform.", 
    "Best regards, Company Team"
);

// Clone and personalize for different users
$johnEmail = $welcomeTemplate->createPersonalizedEmail();
$johnEmail->subject = "Welcome John!";
$johnEmail->body = "Dear John, welcome to our platform.";

$mariaEmail = $welcomeTemplate->createPersonalizedEmail();
$mariaEmail->subject = "Welcome Maria!";
$mariaEmail->body = "Dear Maria, welcome to our platform.";

echo "Template:\n" . $welcomeTemplate->show() . "\n\n";
echo "John's Email:\n" . $johnEmail->show() . "\n\n";
echo "Maria's Email:\n" . $mariaEmail->show() . "\n";
?>
```

## Key Points

1. **`clone` keyword** creates a copy of the object
2. **Faster than `new`** - no constructor overhead  
3. **Modify only what's different** - keep the rest same
4. **Perfect for templates** - one base, many variations

## When to Use Prototype Pattern

✅ **Use when:**
- You need many similar objects with slight differences
- Object creation is expensive (complex initialization)
- You want to avoid subclassing
- You need to create objects at runtime

❌ **Don't use when:**
- Objects are simple to create
- You need completely different objects
- Memory usage is a concern (cloning uses more memory)

## Benefits

- **Performance** - Cloning is faster than constructor calls
- **Flexibility** - Create variations without knowing exact class
- **Simplicity** - Less code duplication
- **Dynamic** - Create objects based on existing ones at runtime
