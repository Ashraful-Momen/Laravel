# Abstract Factory Pattern in PHP - Simple Guide

## Definition
**A collection of Factory methods.** Provides an interface for creating families of related objects without specifying their concrete classes.

## Simple Concept
Instead of one factory making one type of object, you have a factory that makes a complete SET of related objects that work together.

## Real-life Use Cases
- Cross-platform UI components (Windows/Mac buttons, menus, etc.)
- Different database systems (MySQL/PostgreSQL connections, queries, etc.)
- Different notification systems (Email/SMS senders, formatters, etc.)
- Restaurant menu systems (Italian/Chinese food with appetizers, mains, desserts)

## Simple Example

```php
<?php
// Step 1: Define what products we want to create
interface Pizza 
{
    public function prepare();
}

interface Pasta 
{
    public function cook();
}

// Step 2: Create Italian versions
class ItalianPizza implements Pizza 
{
    public function prepare() 
    {
        return "Preparing authentic Italian pizza with fresh mozzarella";
    }
}

class ItalianPasta implements Pasta 
{
    public function cook() 
    {
        return "Cooking Italian pasta with traditional sauce";
    }
}

// Step 3: Create American versions
class AmericanPizza implements Pizza 
{
    public function prepare() 
    {
        return "Preparing American-style pizza with lots of cheese";
    }
}

class AmericanPasta implements Pasta 
{
    public function cook() 
    {
        return "Cooking American pasta with creamy sauce";
    }
}

// Step 4: Abstract Factory - defines what factories should create
interface RestaurantFactory 
{
    public function createPizza(): Pizza;
    public function createPasta(): Pasta;
}

// Step 5: Concrete Factories - actually create the objects
class ItalianRestaurantFactory implements RestaurantFactory 
{
    public function createPizza(): Pizza 
    {
        return new ItalianPizza();
    }
    
    public function createPasta(): Pasta 
    {
        return new ItalianPasta();
    }
}

class AmericanRestaurantFactory implements RestaurantFactory 
{
    public function createPizza(): Pizza 
    {
        return new AmericanPizza();
    }
    
    public function createPasta(): Pasta 
    {
        return new AmericanPasta();
    }
}

// Step 6: Usage - The Magic Happens Here!
function orderMeal($restaurantType) 
{
    // Choose factory based on restaurant type
    if ($restaurantType == 'italian') {
        $factory = new ItalianRestaurantFactory();
    } else {
        $factory = new AmericanRestaurantFactory();
    }
    
    // Create a complete meal (family of related objects)
    $pizza = $factory->createPizza();
    $pasta = $factory->createPasta();
    
    echo "=== {$restaurantType} Restaurant ===\n";
    echo $pizza->prepare() . "\n";
    echo $pasta->cook() . "\n\n";
}

// Test both restaurants
orderMeal('italian');
orderMeal('american');

echo "=== Real-World Example: Mobile App UI ===\n";

// UI Components
interface Button 
{
    public function render();
}

interface Menu 
{
    public function display();
}

// iOS Components
class IOSButton implements Button 
{
    public function render() 
    {
        return "Rendering iOS-style rounded button";
    }
}

class IOSMenu implements Menu 
{
    public function display() 
    {
        return "Displaying iOS-style slide menu";
    }
}

// Android Components
class AndroidButton implements Button 
{
    public function render() 
    {
        return "Rendering Android Material Design button";
    }
}

class AndroidMenu implements Menu 
{
    public function display() 
    {
        return "Displaying Android hamburger menu";
    }
}

// Abstract Factory for UI
interface UIFactory 
{
    public function createButton(): Button;
    public function createMenu(): Menu;
}

// Concrete Factories
class IOSUIFactory implements UIFactory 
{
    public function createButton(): Button 
    {
        return new IOSButton();
    }
    
    public function createMenu(): Menu 
    {
        return new IOSMenu();
    }
}

class AndroidUIFactory implements UIFactory 
{
    public function createButton(): Button 
    {
        return new AndroidButton();
    }
    
    public function createMenu(): Menu 
    {
        return new AndroidMenu();
    }
}

// App that works on both platforms
class MobileApp 
{
    private $factory;
    
    public function __construct(UIFactory $factory) 
    {
        $this->factory = $factory;
    }
    
    public function createUI() 
    {
        $button = $this->factory->createButton();
        $menu = $this->factory->createMenu();
        
        echo $button->render() . "\n";
        echo $menu->display() . "\n";
    }
}

// Usage - Same app, different UI for each platform
$userDevice = 'ios'; // Could be detected automatically

if ($userDevice == 'ios') {
    $factory = new IOSUIFactory();
    echo "=== iOS App ===\n";
} else {
    $factory = new AndroidUIFactory();
    echo "=== Android App ===\n";
}

$app = new MobileApp($factory);
$app->createUI();
?>
```

## Key Differences from Factory Method

| Factory Method | Abstract Factory |
|---|---|
| Creates **ONE type** of object | Creates **FAMILY of related** objects |
| `PaymentFactory::createProcessor()` | `RestaurantFactory::createPizza() + createPasta()` |
| Single responsibility | Multiple related responsibilities |

## When to Use Abstract Factory

✅ **Use when:**
- You need to create families of related objects
- Objects must be used together (iOS button + iOS menu)
- You want to switch entire product families at once
- You need to ensure compatibility between objects

❌ **Don't use when:**
- You only need to create one type of object (use Factory Method)
- Objects are not related to each other
- You rarely change the entire family of objects

## Benefits

- **Consistency** - All objects from same factory work together
- **Easy switching** - Change entire family by changing factory
- **Isolation** - Client code doesn't know concrete classes
- **Scalability** - Easy to add new families (like WindowsUIFactory)

## Real-World Analogy

The simple concept is:

Factory Method = Creates ONE thing (like pizza)
Abstract Factory = Creates a COMPLETE SET of related things (pizza + pasta + dessert)

Key insight: All the objects created by one factory are designed to work together, like iOS button + iOS menu, or Italian pizza + Italian pasta. You don't mix iOS button with Android menu!
It's like having different complete kitchen sets - you get the Italian kitchen with Italian tools, or the American kitchen with American tools, but you don't mix them!

Think of a **car manufacturer**:
- **BMW Factory** creates: BMW Engine + BMW Wheels + BMW Interior
- **Toyota Factory** creates: Toyota Engine + Toyota Wheels + Toyota Interior

You don't mix BMW engine with Toyota wheels - they're designed as a family!
