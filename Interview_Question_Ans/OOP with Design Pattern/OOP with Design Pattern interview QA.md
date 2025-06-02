# Complete PHP OOP Guide - Zero to Advanced

## Table of Contents
1. [OOP Fundamentals](#oop-fundamentals)
2. [Classes and Objects](#classes-and-objects)
3. [Properties and Methods](#properties-and-methods)
4. [Encapsulation](#encapsulation)
5. [Inheritance](#inheritance)
6. [Polymorphism](#polymorphism)
7. [Abstraction](#abstraction)
8. [Interfaces](#interfaces)
9. [Traits](#traits)
10. [Static Members](#static-members)
11. [Magic Methods](#magic-methods)
12. [Advanced Concepts](#advanced-concepts)
13. [Design Patterns](#design-patterns)
14. [Interview Questions](#interview-questions)

---

## OOP Fundamentals

### Definition
Object-Oriented Programming (OOP) is a programming paradigm that organizes code into objects containing data (properties) and functions (methods).

### Why Use OOP?
- **Code Reusability** - Write once, use multiple times
- **Maintainability** - Easier to modify and debug
- **Modularity** - Organized, structured code
- **Real-world Modeling** - Objects represent real entities

### Four Pillars of OOP
1. **Encapsulation** - Data hiding and bundling
2. **Inheritance** - Code reuse through parent-child relationships
3. **Polymorphism** - One interface, multiple implementations
4. **Abstraction** - Hide complexity, show essentials

---

## Classes and Objects

### Definition
- **Class**: Blueprint/template for creating objects
- **Object**: Instance of a class

### Why Use Classes?
- Group related data and functions
- Create reusable code templates
- Model real-world entities

```php
<?php
// Class definition
class Car {
    // Properties (data)
    public $brand;
    public $model;
    public $color;
    
    // Method (function)
    public function start() {
        return "Car is starting...";
    }
    
    public function getInfo() {
        return "Brand: {$this->brand}, Model: {$this->model}, Color: {$this->color}";
    }
}

// Creating objects (instances)
$car1 = new Car();
$car1->brand = "Toyota";
$car1->model = "Camry";
$car1->color = "Red";

$car2 = new Car();
$car2->brand = "Honda";
$car2->model = "Civic";
$car2->color = "Blue";

echo $car1->getInfo(); // Brand: Toyota, Model: Camry, Color: Red
echo $car1->start();   // Car is starting...
?>
```

### Key Points
- Use `new` keyword to create objects
- `$this` refers to current object
- `->` operator accesses properties/methods

---

## Properties and Methods

### Constructor and Destructor

```php
<?php
class Car {
    public $brand;
    public $model;
    private $engineStarted = false;
    
    // Constructor - called when object is created
    public function __construct($brand, $model) {
        $this->brand = $brand;
        $this->model = $model;
        echo "Car created: {$brand} {$model}\n";
    }
    
    // Destructor - called when object is destroyed
    public function __destruct() {
        echo "Car destroyed: {$this->brand} {$this->model}\n";
    }
    
    public function start() {
        $this->engineStarted = true;
        return "Engine started";
    }
    
    public function isRunning() {
        return $this->engineStarted;
    }
}

$car = new Car("Toyota", "Camry"); // Car created: Toyota Camry
echo $car->start(); // Engine started
// When script ends: Car destroyed: Toyota Camry
?>
```

### Why Use Constructor?
- Initialize object properties
- Ensure object is in valid state
- Reduce repetitive code

---

## Encapsulation

### Definition
Bundling data and methods together while restricting direct access to internal components.

### Why Use Encapsulation?
- **Data Protection** - Prevent unauthorized access
- **Validation** - Control how data is set/get
- **Maintainability** - Change internal implementation without affecting external code

### Access Modifiers
- `public` - Accessible everywhere
- `private` - Only within same class
- `protected` - Class and its subclasses

```php
<?php
class BankAccount {
    private $balance;        // Private - cannot access directly
    protected $accountNumber; // Protected - subclasses can access
    public $holderName;      // Public - accessible everywhere
    
    public function __construct($holderName, $initialBalance = 0) {
        $this->holderName = $holderName;
        $this->accountNumber = $this->generateAccountNumber();
        $this->setBalance($initialBalance);
    }
    
    // Getter - controlled access to private data
    public function getBalance() {
        return $this->balance;
    }
    
    // Setter with validation
    private function setBalance($amount) {
        if ($amount >= 0) {
            $this->balance = $amount;
        } else {
            throw new InvalidArgumentException("Balance cannot be negative");
        }
    }
    
    public function deposit($amount) {
        if ($amount > 0) {
            $this->balance += $amount;
            return "Deposited: $amount. New balance: {$this->balance}";
        }
        return "Invalid deposit amount";
    }
    
    public function withdraw($amount) {
        if ($amount > 0 && $amount <= $this->balance) {
            $this->balance -= $amount;
            return "Withdrawn: $amount. Remaining balance: {$this->balance}";
        }
        return "Insufficient funds or invalid amount";
    }
    
    private function generateAccountNumber() {
        return 'ACC' . rand(100000, 999999);
    }
}

$account = new BankAccount("John Doe", 1000);
echo $account->deposit(500);  // Deposited: 500. New balance: 1500
echo $account->withdraw(200); // Withdrawn: 200. Remaining balance: 1300
echo $account->getBalance();  // 1300

// This would cause error:
// echo $account->balance; // Cannot access private property
?>
```

### Key Points
- Use private for sensitive data
- Provide public methods for controlled access
- Validate data in setters

---

## Inheritance

### Definition
Creating new classes based on existing classes, inheriting properties and methods.

### Why Use Inheritance?
- **Code Reuse** - Don't repeat yourself
- **Hierarchical Classification** - Model real-world relationships
- **Extensibility** - Add new features to existing code

### Keywords
- `extends` - Inherit from parent class
- `parent::` - Access parent class methods
- `final` - Prevent inheritance/overriding

```php
<?php
// Parent class (Base class)
class Vehicle {
    protected $brand;
    protected $speed = 0;
    
    public function __construct($brand) {
        $this->brand = $brand;
    }
    
    public function start() {
        return "Vehicle started";
    }
    
    public function accelerate($speed) {
        $this->speed += $speed;
        return "Speed increased to {$this->speed} km/h";
    }
    
    public function getInfo() {
        return "Brand: {$this->brand}, Speed: {$this->speed} km/h";
    }
}

// Child class (Derived class)
class Car extends Vehicle {
    private $doors;
    
    public function __construct($brand, $doors = 4) {
        parent::__construct($brand); // Call parent constructor
        $this->doors = $doors;
    }
    
    // Override parent method
    public function start() {
        return "Car engine started with key";
    }
    
    // Add new method
    public function honk() {
        return "Beep beep!";
    }
    
    // Override with additional functionality
    public function getInfo() {
        return parent::getInfo() . ", Doors: {$this->doors}";
    }
}

class Motorcycle extends Vehicle {
    private $hasSidecar;
    
    public function __construct($brand, $hasSidecar = false) {
        parent::__construct($brand);
        $this->hasSidecar = $hasSidecar;
    }
    
    public function start() {
        return "Motorcycle started with kick/button";
    }
    
    public function wheelie() {
        return "Performing wheelie!";
    }
}

// Usage
$car = new Car("Toyota", 4);
echo $car->start();      // Car engine started with key
echo $car->accelerate(60); // Speed increased to 60 km/h
echo $car->honk();       // Beep beep!
echo $car->getInfo();    // Brand: Toyota, Speed: 60 km/h, Doors: 4

$bike = new Motorcycle("Yamaha");
echo $bike->start();     // Motorcycle started with kick/button
echo $bike->wheelie();   // Performing wheelie!

// Final class - cannot be inherited
final class SportsCar extends Car {
    public function turboBoost() {
        return "Turbo activated!";
    }
}

// This would cause error:
// class SuperSportsCar extends SportsCar {} // Cannot inherit from final class
?>
```

### Key Points
- Child class inherits all public/protected members
- Use `parent::` to access parent methods
- Override methods to change behavior
- Use `final` to prevent further inheritance

---

## Polymorphism

### Definition
Ability to use one interface for different underlying data types or classes.

### Why Use Polymorphism?
- **Flexibility** - Same code works with different objects
- **Extensibility** - Add new types without changing existing code
- **Maintainability** - Reduce conditional logic

### Types
1. **Method Overriding** - Same method name, different implementation
2. **Method Overloading** - Same method name, different parameters (PHP doesn't support true overloading)

```php
<?php
// Base class
abstract class Animal {
    protected $name;
    
    public function __construct($name) {
        $this->name = $name;
    }
    
    // Abstract method - must be implemented by child classes
    abstract public function makeSound();
    
    public function getName() {
        return $this->name;
    }
    
    // Common method
    public function sleep() {
        return "{$this->name} is sleeping";
    }
}

class Dog extends Animal {
    public function makeSound() {
        return "{$this->name} says: Woof! Woof!";
    }
    
    public function fetch() {
        return "{$this->name} is fetching the ball";
    }
}

class Cat extends Animal {
    public function makeSound() {
        return "{$this->name} says: Meow!";
    }
    
    public function climb() {
        return "{$this->name} is climbing the tree";
    }
}

class Cow extends Animal {
    public function makeSound() {
        return "{$this->name} says: Moo!";
    }
}

// Polymorphic function - works with any Animal
function makeAnimalSound(Animal $animal) {
    return $animal->makeSound(); // Calls appropriate method based on object type
}

function animalInfo(Animal $animal) {
    $info = "Name: " . $animal->getName() . "\n";
    $info .= "Sound: " . $animal->makeSound() . "\n";
    $info .= $animal->sleep() . "\n";
    return $info;
}

// Polymorphism in action
$animals = [
    new Dog("Buddy"),
    new Cat("Whiskers"),
    new Cow("Bessie")
];

foreach ($animals as $animal) {
    echo makeAnimalSound($animal) . "\n";
    echo animalInfo($animal) . "\n";
    
    // Type checking for specific methods
    if ($animal instanceof Dog) {
        echo $animal->fetch() . "\n";
    } elseif ($animal instanceof Cat) {
        echo $animal->climb() . "\n";
    }
}

// Method overloading simulation in PHP
class Calculator {
    public function add(...$numbers) {
        return array_sum($numbers);
    }
    
    public function calculate($operation, ...$numbers) {
        switch ($operation) {
            case 'add':
                return array_sum($numbers);
            case 'multiply':
                return array_product($numbers);
            default:
                return 0;
        }
    }
}

$calc = new Calculator();
echo $calc->add(5, 3);           // 8
echo $calc->add(1, 2, 3, 4);     // 10
echo $calc->calculate('add', 5, 3); // 8
?>
```

### Key Points
- Same method name, different implementations
- Use abstract classes/interfaces for contracts
- `instanceof` for type checking
- Reduces conditional logic

---

## Abstraction

### Definition
Hiding complex implementation details while showing only essential features.

### Why Use Abstraction?
- **Simplicity** - Hide complexity from users
- **Focus** - Show only relevant features
- **Security** - Hide implementation details
- **Maintainability** - Change implementation without affecting users

```php
<?php
// Abstract class - cannot be instantiated
abstract class Shape {
    protected $color;
    
    public function __construct($color) {
        $this->color = $color;
    }
    
    // Abstract methods - must be implemented by child classes
    abstract public function calculateArea();
    abstract public function calculatePerimeter();
    
    // Concrete method - shared implementation
    public function getColor() {
        return $this->color;
    }
    
    public function display() {
        echo "Shape: " . get_class($this) . "\n";
        echo "Color: {$this->color}\n";
        echo "Area: " . $this->calculateArea() . "\n";
        echo "Perimeter: " . $this->calculatePerimeter() . "\n";
    }
    
    // Template method pattern
    final public function printShapeInfo() {
        echo "=== Shape Information ===\n";
        $this->display();
        echo "========================\n";
    }
}

class Rectangle extends Shape {
    private $width;
    private $height;
    
    public function __construct($color, $width, $height) {
        parent::__construct($color);
        $this->width = $width;
        $this->height = $height;
    }
    
    public function calculateArea() {
        return $this->width * $this->height;
    }
    
    public function calculatePerimeter() {
        return 2 * ($this->width + $this->height);
    }
}

class Circle extends Shape {
    private $radius;
    
    public function __construct($color, $radius) {
        parent::__construct($color);
        $this->radius = $radius;
    }
    
    public function calculateArea() {
        return pi() * pow($this->radius, 2);
    }
    
    public function calculatePerimeter() {
        return 2 * pi() * $this->radius;
    }
}

// Usage
$rectangle = new Rectangle("Red", 5, 3);
$circle = new Circle("Blue", 4);

$rectangle->printShapeInfo();
// === Shape Information ===
// Shape: Rectangle
// Color: Red
// Area: 15
// Perimeter: 16
// ========================

$circle->printShapeInfo();

// This would cause error:
// $shape = new Shape("Green"); // Cannot instantiate abstract class

// Factory pattern using abstraction
class ShapeFactory {
    public static function createShape($type, $color, ...$dimensions) {
        switch (strtolower($type)) {
            case 'rectangle':
                return new Rectangle($color, $dimensions[0], $dimensions[1]);
            case 'circle':
                return new Circle($color, $dimensions[0]);
            default:
                throw new InvalidArgumentException("Unknown shape type: $type");
        }
    }
}

$shapes = [
    ShapeFactory::createShape('rectangle', 'Green', 4, 6),
    ShapeFactory::createShape('circle', 'Yellow', 3)
];

foreach ($shapes as $shape) {
    $shape->printShapeInfo();
}
?>
```

### Key Points
- Use `abstract` keyword for classes and methods
- Cannot instantiate abstract classes
- Child classes must implement all abstract methods
- Combine concrete and abstract methods

---

## Interfaces

### Definition
Contract that defines what methods a class must implement, without providing implementation.

### Why Use Interfaces?
- **Contract Definition** - Ensure classes implement required methods
- **Multiple Inheritance** - Class can implement multiple interfaces
- **Polymorphism** - Treat different classes uniformly
- **Loose Coupling** - Depend on contracts, not implementations

```php
<?php
// Interface definition
interface Drawable {
    public function draw();
    public function getArea();
}

interface Colorable {
    public function setColor($color);
    public function getColor();
}

interface Movable {
    public function move($x, $y);
    public function getPosition();
}

// Class implementing multiple interfaces
class Square implements Drawable, Colorable, Movable {
    private $side;
    private $color;
    private $x = 0;
    private $y = 0;
    
    public function __construct($side, $color = 'white') {
        $this->side = $side;
        $this->color = $color;
    }
    
    // Implement Drawable interface
    public function draw() {
        return "Drawing a {$this->color} square with side {$this->side} at ({$this->x}, {$this->y})";
    }
    
    public function getArea() {
        return $this->side * $this->side;
    }
    
    // Implement Colorable interface
    public function setColor($color) {
        $this->color = $color;
    }
    
    public function getColor() {
        return $this->color;
    }
    
    // Implement Movable interface
    public function move($x, $y) {
        $this->x = $x;
        $this->y = $y;
        return "Moved to ({$this->x}, {$this->y})";
    }
    
    public function getPosition() {
        return ['x' => $this->x, 'y' => $this->y];
    }
}

class Circle implements Drawable, Colorable {
    private $radius;
    private $color;
    
    public function __construct($radius, $color = 'white') {
        $this->radius = $radius;
        $this->color = $color;
    }
    
    public function draw() {
        return "Drawing a {$this->color} circle with radius {$this->radius}";
    }
    
    public function getArea() {
        return pi() * pow($this->radius, 2);
    }
    
    public function setColor($color) {
        $this->color = $color;
    }
    
    public function getColor() {
        return $this->color;
    }
}

// Interface inheritance
interface Shape3D extends Drawable {
    public function getVolume();
}

class Cube implements Shape3D, Colorable, Movable {
    private $side;
    private $color;
    private $x = 0, $y = 0;
    
    public function __construct($side, $color = 'white') {
        $this->side = $side;
        $this->color = $color;
    }
    
    public function draw() {
        return "Drawing a 3D {$this->color} cube with side {$this->side}";
    }
    
    public function getArea() {
        return 6 * pow($this->side, 2); // Surface area
    }
    
    public function getVolume() {
        return pow($this->side, 3);
    }
    
    public function setColor($color) { $this->color = $color; }
    public function getColor() { return $this->color; }
    public function move($x, $y) { $this->x = $x; $this->y = $y; }
    public function getPosition() { return ['x' => $this->x, 'y' => $this->y]; }
}

// Polymorphism with interfaces
function drawShape(Drawable $shape) {
    echo $shape->draw() . "\n";
    echo "Area: " . $shape->getArea() . "\n";
}

function changeColor(Colorable $object, $newColor) {
    $oldColor = $object->getColor();
    $object->setColor($newColor);
    echo "Changed color from {$oldColor} to {$newColor}\n";
}

// Usage
$square = new Square(5, 'red');
$circle = new Circle(3, 'blue');
$cube = new Cube(4, 'green');

$shapes = [$square, $circle, $cube];

foreach ($shapes as $shape) {
    drawShape($shape);
    
    if ($shape instanceof Colorable) {
        changeColor($shape, 'purple');
    }
    
    if ($shape instanceof Movable) {
        echo $shape->move(10, 20) . "\n";
    }
    
    if ($shape instanceof Shape3D) {
        echo "Volume: " . $shape->getVolume() . "\n";
    }
    
    echo "---\n";
}

// Constants in interfaces
interface DatabaseConnection {
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 3306;
    
    public function connect();
    public function disconnect();
}

class MySQLConnection implements DatabaseConnection {
    public function connect() {
        return "Connecting to MySQL at " . self::DEFAULT_HOST . ":" . self::DEFAULT_PORT;
    }
    
    public function disconnect() {
        return "Disconnecting from MySQL";
    }
}
?>
```

### Key Points
- Use `interface` keyword
- All methods are public and abstract
- Classes use `implements` keyword
- Can implement multiple interfaces
- Support constants

---

## Traits

### Definition
Mechanism for code reuse in single inheritance languages. Traits are similar to classes but intended to group functionality in a fine-grained and consistent way.

### Why Use Traits?
- **Horizontal Code Reuse** - Share code across unrelated classes
- **Avoid Code Duplication** - DRY principle
- **Composition over Inheritance** - More flexible than inheritance
- **Multiple Usage** - Class can use multiple traits

```php
<?php
// Basic trait
trait Loggable {
    private $logs = [];
    
    public function log($message) {
        $this->logs[] = date('Y-m-d H:i:s') . ": " . $message;
    }
    
    public function getLogs() {
        return $this->logs;
    }
    
    public function clearLogs() {
        $this->logs = [];
    }
}

trait Timestampable {
    private $createdAt;
    private $updatedAt;
    
    public function setTimestamps() {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }
    
    public function touch() {
        $this->updatedAt = new DateTime();
    }
    
    public function getCreatedAt() {
        return $this->createdAt;
    }
    
    public function getUpdatedAt() {
        return $this->updatedAt;
    }
}

trait Cacheable {
    private static $cache = [];
    
    public function cache($key, $value) {
        self::$cache[$key] = $value;
    }
    
    public function getFromCache($key) {
        return self::$cache[$key] ?? null;
    }
    
    public function clearCache() {
        self::$cache = [];
    }
}

// Using single trait
class User {
    use Loggable;
    
    private $name;
    private $email;
    
    public function __construct($name, $email) {
        $this->name = $name;
        $this->email = $email;
        $this->log("User created: {$name}");
    }
    
    public function updateEmail($email) {
        $oldEmail = $this->email;
        $this->email = $email;
        $this->log("Email updated from {$oldEmail} to {$email}");
    }
}

// Using multiple traits
class Product {
    use Loggable, Timestampable, Cacheable;
    
    private $name;
    private $price;
    
    public function __construct($name, $price) {
        $this->name = $name;
        $this->price = $price;
        $this->setTimestamps();
        $this->log("Product created: {$name} - \${$price}");
    }
    
    public function updatePrice($price) {
        $oldPrice = $this->price;
        $this->price = $price;
        $this->touch();
        $this->log("Price updated from \${$oldPrice} to \${$price}");
        
        // Cache the update
        $this->cache("last_price_update", $this->getUpdatedAt());
    }
}

// Trait with abstract methods
trait Exportable {
    abstract public function getData();
    
    public function exportToJson() {
        return json_encode($this->getData());
    }
    
    public function exportToXml() {
        $data = $this->getData();
        $xml = new SimpleXMLElement('<root/>');
        array_walk_recursive($data, [$xml, 'addChild']);
        return $xml->asXML();
    }
}

class Order {
    use Exportable, Loggable;
    
    private $id;
    private $items;
    private $total;
    
    public function __construct($id, $items, $total) {
        $this->id = $id;
        $this->items = $items;
        $this->total = $total;
        $this->log("Order created: {$id}");
    }
    
    // Implement abstract method from Exportable trait
    public function getData() {
        return [
            'id' => $this->id,
            'items' => $this->items,
            'total' => $this->total
        ];
    }
}

// Trait conflict resolution
trait TraitA {
    public function test() {
        return "From Trait A";
    }
    
    public function hello() {
        return "Hello from Trait A";
    }
}

trait TraitB {
    public function test() {
        return "From Trait B";
    }
    
    public function hello() {
        return "Hello from Trait B";
    }
}

class ConflictResolver {
    use TraitA, TraitB {
        TraitA::test insteadof TraitB;  // Use TraitA's test method
        TraitB::test as testB;          // Alias TraitB's test method
        TraitA::hello insteadof TraitB; // Use TraitA's hello method
    }
    
    public function showTests() {
        echo "Default test: " . $this->test() . "\n";      // From Trait A
        echo "TraitB test: " . $this->testB() . "\n";      // From Trait B
        echo "Hello: " . $this->hello() . "\n";            // Hello from Trait A
    }
}

// Trait composing other traits
trait FullFeature {
    use Loggable, Timestampable {
        Loggable::log as logMessage;
    }
    
    public function initialize() {
        $this->setTimestamps();
        $this->logMessage("Object initialized");
    }
}

class Document {
    use FullFeature;
    
    private $title;
    
    public function __construct($title) {
        $this->title = $title;
        $this->initialize();
    }
}

// Usage examples
$user = new User("John Doe", "john@example.com");
$user->updateEmail("john.doe@example.com");
print_r($user->getLogs());

$product = new Product("Laptop", 999.99);
$product->updatePrice(899.99);
echo $product->exportToJson() . "\n";

$order = new Order("ORD001", ["Laptop", "Mouse"], 950.99);
echo $order->exportToJson() . "\n";

$resolver = new ConflictResolver();
$resolver->showTests();

$doc = new Document("My Document");
print_r($doc->getLogs());
?>
```

### Key Points
- Use `trait` keyword to define
- Use `use` keyword in class to include
- Resolve conflicts with `insteadof` and `as`
- Can contain abstract methods
- Support method aliasing

---

## Static Members

### Definition
Properties and methods that belong to the class itself rather than instances of the class.

### Why Use Static Members?
- **Shared Data** - Same value across all instances
- **Utility Functions** - Methods that don't need object state
- **Class-level Operations** - Counters, configurations
- **Memory Efficiency** - One copy for all instances

```php
<?php
class Counter {
    private static $count = 0;           // Static property
    private static $instances = [];      // Track instances
    public static $maxCount = 100;      // Public static property
    
    private $id;
    
    public function __construct() {
        self::$count++;                  // Increment static counter
        $this->id = self::$count;
        self::$instances[] = $this;      // Store instance
        
        if (self::$count > self::$maxCount) {
            throw new Exception("Maximum instances exceeded");
        }
    }
    
    // Static method
    public static function getCount() {
        return self::$count;
    }
    
    public static function getInstances() {
        return self::$instances;
    }
    
    public static function reset() {
        self::$count = 0;
        self::$instances = [];
    }
    
    public function getId() {
        return $this->id;
    }
    
    // Static method with late static binding
    public static function getClassName() {
        return static::class;  // Returns actual class name
    }
}

class SpecialCounter extends Counter {
    public static function getClassName() {
        return "Special: " . static::class;
    }
}

// Utility class with static methods
class MathHelper {
    public static function add($a, $b) {
        return $a + $b;
    }
    
    public static function multiply($a, $b) {
        return $a * $b;
    }
    
    public static function factorial($n) {
        if ($n <= 1) return 1;
        return $n * self::factorial($n - 1);
    }
    
    public static function isPrime($n) {
        if ($n < 2) return false;
        for ($i = 2; $i <= sqrt($n); $i++) {
            if ($n % $i === 0) return false;
        }
        return true;
    }
}

// Configuration class
class Config {
    private static $settings = [
        'database_host' => 'localhost',
        'database_port' => 3306,
        'debug_mode' => false
    ];
    
    public static function get($key, $default = null) {
        return self::$settings[$key] ?? $default;
    }
    
    public static function set($key, $value) {
        self::$settings[$key] = $value;
    }
    
    public static function getAll() {
        return self::$settings;
    }
}

// Singleton pattern using static
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connection = "Database connection established";
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    private function __wakeup() {}
}

// Usage examples
$counter1 = new Counter();
$counter2 = new Counter();
$counter3 = new Counter();

echo Counter::getCount(); // 3
echo $counter1->getId();  // 1
echo $counter2->getId();  // 2

echo MathHelper::add(5, 3);        // 8
echo MathHelper::factorial(5);     // 120
echo MathHelper::isPrime(17) ? "Prime" : "Not Prime"; // Prime

Config::set('debug_mode', true);
echo Config::get('debug_mode') ? "Debug ON" : "Debug OFF"; // Debug ON

$db1 = Database::getInstance();
$db2 = Database::getInstance();
var_dump($db1 === $db2); // true (same instance)

// Late static binding
echo Counter::getClassName();        // Counter
echo SpecialCounter::getClassName(); // Special: SpecialCounter
?>
```

### Key Points
- Use `static` keyword for properties and methods
- Access with `::` (scope resolution operator)
- `self::` refers to current class
- `static::` for late static binding
- Cannot access non-static members from static context

---

## Magic Methods

### Definition
Special methods that are automatically called when certain actions are performed on objects.

### Why Use Magic Methods?
- **Object Behavior Customization** - Define how objects behave in various situations
- **Operator Overloading** - Make objects behave like built-in types
- **Dynamic Properties** - Handle undefined properties/methods
- **Serialization Control** - Custom serialization logic

```php
<?php
class MagicExample {
    private $data = [];
    private $metadata = [];
    
    // Constructor - called when object is created
    public function __construct($initialData = []) {
        $this->data = $initialData;
        echo "Object constructed\n";
    }
    
    // Destructor - called when object is destroyed
    public function __destruct() {
        echo "Object destructed\n";
    }
    
    // Called when accessing undefined properties
    public function __get($name) {
        echo "Getting property: $name\n";
        return $this->data[$name] ?? null;
    }
    
    // Called when setting undefined properties
    public function __set($name, $value) {
        echo "Setting property: $name = $value\n";
        $this->data[$name] = $value;
    }
    
    // Called when checking if undefined property exists
    public function __isset($name) {
        echo "Checking if property exists: $name\n";
        return isset($this->data[$name]);
    }
    
    // Called when unsetting undefined properties
    public function __unset($name) {
        echo "Unsetting property: $name\n";
        unset($this->data[$name]);
    }
    
    // Called when object is treated as string
    public function __toString() {
        return "MagicExample: " . json_encode($this->data);
    }
    
    // Called when calling undefined methods
    public function __call($name, $arguments) {
        echo "Calling method: $name with arguments: " . implode(', ', $arguments) . "\n";
        
        // Dynamic method handling
        if (strpos($name, 'get') === 0) {
            $property = lcfirst(substr($name, 3));
            return $this->data[$property] ?? null;
        }
        
        if (strpos($name, 'set') === 0) {
            $property = lcfirst(substr($name, 3));
            $this->data[$property] = $arguments[0] ?? null;
            return $this;
        }
        
        throw new BadMethodCallException("Method $name does not exist");
    }
    
    // Called when calling undefined static methods
    public static function __callStatic($name, $arguments) {
        echo "Calling static method: $name with arguments: " . implode(', ', $arguments) . "\n";
        return "Static method $name called";
    }
    
    // Called when object is used as function
    public function __invoke($message) {
        echo "Object invoked with message: $message\n";
        return "Invoked: $message";
    }
    
    // Called when var_dump() is used
    public function __debugInfo() {
        return [
            'data' => $this->data,
            'data_count' => count($this->data)
        ];
    }
    
    // Called during serialization
    public function __sleep() {
        echo "Object is being serialized\n";
        return ['data']; // Only serialize 'data' property
    }
    
    // Called during unserialization
    public function __wakeup() {
        echo "Object is being unserialized\n";
        $this->metadata = ['unserialized_at' => date('Y-m-d H:i:s')];
    }
    
    // Called when object is cloned
    public function __clone() {
        echo "Object is being cloned\n";
        $this->data = array_merge($this->data, ['cloned_at' => date('Y-m-d H:i:s')]);
    }
}

// Array access magic methods
class ArrayAccessExample implements ArrayAccess {
    private $data = [];
    
    // Check if offset exists
    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }
    
    // Get value at offset
    public function offsetGet($offset): mixed {
        return $this->data[$offset] ?? null;
    }
    
    // Set value at offset
    public function offsetSet($offset, $value): void {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }
    
    // Unset offset
    public function offsetUnset($offset): void {
        unset($this->data[$offset]);
    }
    
    public function getData() {
        return $this->data;
    }
}

// Iterator magic methods
class IteratorExample implements Iterator {
    private $data = [];
    private $position = 0;
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    public function rewind(): void {
        $this->position = 0;
    }
    
    public function current(): mixed {
        return $this->data[$this->position];
    }
    
    public function key(): mixed {
        return $this->position;
    }
    
    public function next(): void {
        $this->position++;
    }
    
    public function valid(): bool {
        return isset($this->data[$this->position]);
    }
}

// Usage examples
echo "=== Magic Methods Demo ===\n";

$obj = new MagicExample(['name' => 'John', 'age' => 30]);

// __get and __set
echo $obj->name;        // Getting property: name -> John
$obj->email = 'john@example.com'; // Setting property: email = john@example.com

// __isset and __unset
echo isset($obj->age) ? "Age exists" : "Age not exists"; // Checking if property exists: age
unset($obj->age);       // Unsetting property: age

// __toString
echo $obj;              // MagicExample: {"name":"John","email":"john@example.com"}

// __call
echo $obj->getName();   // Calling method: getName -> John
$obj->setCity('New York'); // Calling method: setCity with arguments: New York

// __callStatic
echo MagicExample::staticMethod('test'); // Calling static method: staticMethod

// __invoke
echo $obj('Hello World'); // Object invoked with message: Hello World

// __debugInfo
var_dump($obj);

// __sleep and __wakeup
$serialized = serialize($obj);   // Object is being serialized
$unserialized = unserialize($serialized); // Object is being unserialized

// __clone
$cloned = clone $obj;    // Object is being cloned

// Array access example
echo "\n=== Array Access Demo ===\n";
$arrayObj = new ArrayAccessExample();
$arrayObj['name'] = 'Alice';
$arrayObj['age'] = 25;
$arrayObj[] = 'Extra item';

echo $arrayObj['name'];  // Alice
echo isset($arrayObj['age']) ? "Age exists" : "Age not exists";
unset($arrayObj['age']);
print_r($arrayObj->getData());

// Iterator example
echo "\n=== Iterator Demo ===\n";
$iterator = new IteratorExample(['a', 'b', 'c', 'd']);
foreach ($iterator as $key => $value) {
    echo "Key: $key, Value: $value\n";
}
?>
```

### Key Magic Methods Summary
- `__construct()` - Object creation
- `__destruct()` - Object destruction
- `__get()/__set()` - Property access
- `__call()/__callStatic()` - Method calls
- `__toString()` - String conversion
- `__invoke()` - Function-like usage
- `__clone()` - Object cloning
- `__sleep()/__wakeup()` - Serialization

---

## Advanced Concepts

### 1. Namespaces

```php
<?php
// File: Models/User.php
namespace App\Models;

class User {
    private $name;
    
    public function __construct($name) {
        $this->name = $name;
    }
    
    public function getName() {
        return $this->name;
    }
}

// File: Services/UserService.php
namespace App\Services;

use App\Models\User;
use App\Models\User as UserModel; // Alias

class UserService {
    public function createUser($name) {
        return new User($name);
    }
    
    public function processUser(UserModel $user) {
        return "Processing: " . $user->getName();
    }
}

// Usage
use App\Services\UserService;
use App\Models\User;

$service = new UserService();
$user = $service->createUser('John');
echo $service->processUser($user);
?>
```

### 2. Type Declarations and Return Types

```php
<?php
declare(strict_types=1);

class TypeExample {
    private string $name;
    private int $age;
    private array $skills;
    
    public function __construct(string $name, int $age, array $skills = []) {
        $this->name = $name;
        $this->age = $age;
        $this->skills = $skills;
    }
    
    public function getName(): string {
        return $this->name;
    }
    
    public function getAge(): int {
        return $this->age;
    }
    
    public function addSkill(string $skill): self {
        $this->skills[] = $skill;
        return $this;
    }
    
    public function getSkills(): array {
        return $this->skills;
    }
    
    public function findSkill(string $skill): ?string {
        $index = array_search($skill, $this->skills);
        return $index !== false ? $this->skills[$index] : null;
    }
    
    public function processCallback(callable $callback): mixed {
        return $callback($this);
    }
}

// Union types (PHP 8.0+)
function processValue(int|string $value): int|string {
    if (is_int($value)) {
        return $value * 2;
    }
    return strtoupper($value);
}

// Usage
$person = new TypeExample('John', 30, ['PHP', 'JavaScript']);
$person->addSkill('Python')->addSkill('Go');

echo $person->getName();     // John
echo $person->findSkill('PHP') ?? 'Not found'; // PHP
?>
```

### 3. Error Handling and Exceptions

```php
<?php
// Custom exception classes
class ValidationException extends Exception {
    private $errors;
    
    public function __construct($message, $errors = [], $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }
    
    public function getErrors() {
        return $this->errors;
    }
}

class DatabaseException extends Exception {}

class User {
    private $email;
    private $age;
    
    public function setEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(
                'Invalid email format',
                ['email' => 'Must be a valid email address']
            );
        }
        $this->email = $email;
    }
    
    public function setAge(int $age): void {
        if ($age < 0 || $age > 150) {
            throw new ValidationException(
                'Invalid age',
                ['age' => 'Age must be between 0 and 150']
            );
        }
        $this->age = $age;
    }
    
    public function save(): bool {
        // Simulate database operation
        if (rand(0, 1)) {
            throw new DatabaseException('Failed to save user to database');
        }
        return true;
    }
}

// Error handling
try {
    $user = new User();
    $user->setEmail('invalid-email');
    $user->setAge(200);
    $user->save();
} catch (ValidationException $e) {
    echo "Validation Error: " . $e->getMessage() . "\n";
    print_r($e->getErrors());
} catch (DatabaseException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
} finally {
    echo "Cleanup operations\n";
}
?>
```

### 4. Dependency Injection

```php
<?php
// Dependency Injection Container
class Container {
    private $bindings = [];
    private $instances = [];
    
    public function bind(string $abstract, $concrete = null): void {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }
    
    public function singleton(string $abstract, $concrete = null): void {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }
    
    public function resolve(string $abstract) {
        // Return singleton if exists
        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }
        
        $concrete = $this->bindings[$abstract] ?? $abstract;
        
        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }
        
        // Store singleton
        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    private function build(string $concrete) {
        $reflection = new ReflectionClass($concrete);
        
        if (!$reflection->isInstantiable()) {
            throw new Exception("Cannot instantiate $concrete");
        }
        
        $constructor = $reflection->getConstructor();
        
        if ($constructor === null) {
            return new $concrete;
        }
        
        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->resolve($type->getName());
            }
        }
        
        return $reflection->newInstanceArgs($dependencies);
    }
}

// Example classes
interface LoggerInterface {
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface {
    public function log(string $message): void {
        echo "Logging to file: $message\n";
    }
}

interface DatabaseInterface {
    public function save(array $data): bool;
}

class MySQLDatabase implements DatabaseInterface {
    public function save(array $data): bool {
        echo "Saving to MySQL: " . json_encode($data) . "\n";
        return true;
    }
}

class UserService {
    private $logger;
    private $database;
    
    public function __construct(LoggerInterface $logger, DatabaseInterface $database) {
        $this->logger = $logger;
        $this->database = $database;
    }
    
    public function createUser(string $name, string $email): bool {
        $this->logger->log("Creating user: $name");
        
        $userData = ['name' => $name, 'email' => $email];
        $result = $this->database->save($userData);
        
        if ($result) {
            $this->logger->log("User created successfully: $name");
        }
        
        return $result;
    }
}

// Container setup
$container = new Container();

$container->bind(LoggerInterface::class, FileLogger::class);
$container->bind(DatabaseInterface::class, MySQLDatabase::class);
$container->bind(UserService::class);

// Usage
$userService = $container->resolve(UserService::class);
$userService->createUser('John Doe', 'john@example.com');
?>
```

---

## Design Patterns

### 1. Factory Pattern

```php
<?php
interface VehicleInterface {
    public function start(): string;
    public function stop(): string;
}

class Car implements VehicleInterface {
    public function start(): string {
        return "Car started with key";
    }
    
    public function stop(): string {
        return "Car stopped";
    }
}

class Motorcycle implements VehicleInterface {
    public function start(): string {
        return "Motorcycle started with button";
    }
    
    public function stop(): string {
        return "Motorcycle stopped";
    }
}

// Simple Factory
class VehicleFactory {
    public static function create(string $type): VehicleInterface {
        return match (strtolower($type)) {
            'car' => new Car(),
            'motorcycle' => new Motorcycle(),
            default => throw new InvalidArgumentException("Unknown vehicle type: $type")
        };
    }
}

// Factory Method Pattern
abstract class VehicleCreator {
    abstract public function createVehicle(): VehicleInterface;
    
    public function deliverVehicle(): string {
        $vehicle = $this->createVehicle();
        return $vehicle->start() . " and delivered";
    }
}

class CarCreator extends VehicleCreator {
    public function createVehicle(): VehicleInterface {
        return new Car();
    }
}

// Usage
$car = VehicleFactory::create('car');
echo $car->start(); // Car started with key

$carCreator = new CarCreator();
echo $carCreator->deliverVehicle(); // Car started with key and delivered
?>
```

### 2. Observer Pattern

```php
<?php
interface ObserverInterface {
    public function update(string $event, $data): void;
}

interface SubjectInterface {
    public function attach(ObserverInterface $observer): void;
    public function detach(ObserverInterface $observer): void;
    public function notify(string $event, $data = null): void;
}

class User implements SubjectInterface {
    private $observers = [];
    private $name;
    private $email;
    
    public function __construct(string $name, string $email) {
        $this->name = $name;
        $this->email = $email;
    }
    
    public function attach(ObserverInterface $observer): void {
        $this->observers[] = $observer;
    }
    
    public function detach(ObserverInterface $observer): void {
        $key = array_search($observer, $this->observers);
        if ($key !== false) {
            unset($this->observers[$key]);
        }
    }
    
    public function notify(string $event, $data = null): void {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }
    
    public function setEmail(string $email): void {
        $oldEmail = $this->email;
        $this->email = $email;
        $this->notify('email_changed', ['old' => $oldEmail, 'new' => $email]);
    }
    
    public function getName(): string {
        return $this->name;
    }
}

class EmailNotificationObserver implements ObserverInterface {
    public function update(string $event, $data): void {
        if ($event === 'email_changed') {
            echo "Email notification: Email changed from {$data['old']} to {$data['new']}\n";
        }
    }
}

class LogObserver implements ObserverInterface {
    public function update(string $event, $data): void {
        echo "Log: Event '$event' occurred with data: " . json_encode($data) . "\n";
    }
}

// Usage
$user = new User('John', 'john@old.com');
$user->attach(new EmailNotificationObserver());
$user->attach(new LogObserver());

$user->setEmail('john@new.com');
// Output:
// Email notification: Email changed from john@old.com to john@new.com
// Log: Event 'email_changed' occurred with data: {"old":"john@old.com","new":"john@new.com"}
?>
```

### 3. Strategy Pattern

```php
<?php
interface PaymentStrategyInterface {
    public function pay(float $amount): string;
}

class CreditCardPayment implements PaymentStrategyInterface {
    private $cardNumber;
    
    public function __construct(string $cardNumber) {
        $this->cardNumber = $cardNumber;
    }
    
    public function pay(float $amount): string {
        return "Paid $amount using Credit Card ending in " . substr($this->cardNumber, -4);
    }
}

class PayPalPayment implements PaymentStrategyInterface {
    private $email;
    
    public function __construct(string $email) {
        $this->email = $email;
    }
    
    public function pay(float $amount): string {
        return "Paid $amount using PayPal account: {$this->email}";
    }
}

class PaymentContext {
    private $strategy;
    
    public function setStrategy(PaymentStrategyInterface $strategy): void {
        $this->strategy = $strategy;
    }
    
    public function processPayment(float $amount): string {
        if (!$this->strategy) {
            throw new Exception("Payment strategy not set");
        }
        return $this->strategy->pay($amount);
    }
}

// Usage
$payment = new PaymentContext();

$payment->setStrategy(new CreditCardPayment('1234567890123456'));
echo $payment->processPayment(100.00); // Paid 100 using Credit Card ending in 3456

$payment->setStrategy(new PayPalPayment('user@example.com'));
echo $payment->processPayment(50.00);  // Paid 50 using PayPal account: user@example.com
?>
```

---

## Interview Questions

### **Q1: What are the four pillars of OOP?**

**Answer:**
1. **Encapsulation** - Bundling data and methods, hiding internal details
2. **Inheritance** - Creating new classes from existing ones
3. **Polymorphism** - Same interface, different implementations
4. **Abstraction** - Hiding complexity, showing only essentials

```php
// Example demonstrating all four pillars
abstract class Animal {                    // Abstraction
    protected $name;                       // Encapsulation (protected)
    
    public function __construct($name) {
        $this->name = $name;
    }
    
    abstract public function makeSound();   // Abstraction
    
    public function getName() {             // Encapsulation (controlled access)
        return $this->name;
    }
}

class Dog extends Animal {                 // Inheritance
    public function makeSound() {           // Polymorphism
        return "Woof!";
    }
}

class Cat extends Animal {                 // Inheritance
    public function makeSound() {           // Polymorphism
        return "Meow!";
    }
}
```

### **Q2: What's the difference between `public`, `private`, and `protected`?**

**Answer:**
- **`public`** - Accessible from anywhere
- **`private`** - Only accessible within the same class
- **`protected`** - Accessible within the class and its subclasses

```php
class Example {
    public $publicVar = "Everyone can access";
    private $privateVar = "Only this class";
    protected $protectedVar = "This class and subclasses";
    
    public function testAccess() {
        echo $this->publicVar;    // ✓ Works
        echo $this->privateVar;   // ✓ Works
        echo $this->protectedVar; // ✓ Works
    }
}

class Child extends Example {
    public function testChildAccess() {
        echo $this->publicVar;    // ✓ Works
        echo $this->privateVar;   // ✗ Error
        echo $this->protectedVar; // ✓ Works
    }
}
```

### **Q3: What's the difference between Abstract Class and Interface?**

**Answer:**

| Feature | Abstract Class | Interface |
|---------|---------------|-----------|
| Implementation | Can have concrete methods | Only method signatures |
| Properties | Can have properties | Only constants |
| Inheritance | Single inheritance | Multiple implementation |
| Access Modifiers | All types | Public only |
| Constructor | Can have constructor | Cannot have constructor |

```php
// Abstract Class
abstract class Vehicle {
    protected $brand;                      // Properties allowed
    
    public function __construct($brand) {  // Constructor allowed
        $this->brand = $brand;
    }
    
    public function getBrand() {           // Concrete method
        return $this->brand;
    }
    
    abstract public function start();      // Abstract method
}

// Interface
interface Drivable {
    const MAX_SPEED = 200;                // Constants allowed
    public function accelerate($speed);   // Only method signatures
    public function brake();
}

class Car extends Vehicle implements Drivable {
    public function start() {
        return "Car started";
    }
    
    public function accelerate($speed) {
        return "Accelerating to $speed";
    }
    
    public function brake() {
        return "Braking";
    }
}
```

### **Q4: What are Traits and when to use them?**

**Answer:**
Traits are a mechanism for code reuse in single inheritance languages. Use when you need to share code across unrelated classes.

```php
trait Loggable {
    public function log($message) {
        echo date('Y-m-d H:i:s') . ": $message\n";
    }
}

trait Cacheable {
    private $cache = [];
    
    public function cache($key, $value) {
        $this->cache[$key] = $value;
    }
    
    public function getFromCache($key) {
        return $this->cache[$key] ?? null;
    }
}

class User {
    use Loggable, Cacheable;  // Multiple traits
    
    private $name;
    
    public function __construct($name) {
        $this->name = $name;
        $this->log("User created: $name");
    }
}

// When to use:
// - Need to share code across unrelated classes
// - Want to avoid deep inheritance hierarchies
// - Need multiple inheritance-like behavior
```

### **Q5: What is Method Overriding vs Method Overloading?**

**Answer:**

**Method Overriding** - Child class provides specific implementation of parent method:
```php
class Animal {
    public function makeSound() {
        return "Some sound";
    }
}

class Dog extends Animal {
    public function makeSound() {        // Override
        return "Woof!";
    }
}
```

**Method Overloading** - Multiple methods with same name but different parameters (PHP doesn't support true overloading, but can simulate):
```php
class Calculator {
    public function add(...$numbers) {   // Variable arguments
        return array_sum($numbers);
    }
    
    public function calculate($operation, ...$numbers) {
        switch ($operation) {
            case 'add': return array_sum($numbers);
            case 'multiply': return array_product($numbers);
        }
    }
}
```

### **Q6: What is Polymorphism? Give examples.**

**Answer:**
Polymorphism allows objects of different types to be treated as instances of the same type through inheritance or interfaces.

```php
interface Shape {
    public function calculateArea();
}

class Rectangle implements Shape {
    private $width, $height;
    
    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }
    
    public function calculateArea() {
        return $this->width * $this->height;
    }
}

class Circle implements Shape {
    private $radius;
    
    public function __construct($radius) {
        $this->radius = $radius;
    }
    
    public function calculateArea() {
        return pi() * pow($this->radius, 2);
    }
}

// Polymorphic function
function printArea(Shape $shape) {
    echo "Area: " . $shape->calculateArea() . "\n";
}

// Same function works with different objects
printArea(new Rectangle(5, 3));  // Area: 15
printArea(new Circle(4));        // Area: 50.26...
```

### **Q7: What is `self::` vs `static::` vs `$this->`?**

**Answer:**

- **`$this->`** - Current object instance
- **`self::`** - Current class (early binding)
- **`static::`** - Called class (late static binding)

```php
class Parent {
    public static $name = "Parent";
    
    public function showThis() {
        return $this;               // Current object
    }
    
    public static function showSelf() {
        return self::$name;         // Always "Parent"
    }
    
    public static function showStatic() {
        return static::$name;       // Depends on calling class
    }
}

class Child extends Parent {
    public static $name = "Child";
}

$child = new Child();
echo $child->showThis();          // Child object
echo Child::showSelf();           // "Parent" (self refers to Parent)
echo Child::showStatic();         // "Child" (static refers to Child)
```

### **Q8: What are Magic Methods? Name 5 important ones.**

**Answer:**
Magic methods are special methods that are automatically called when certain actions are performed.

```php
class MagicExample {
    private $data = [];
    
    public function __construct($data = []) {     // 1. Constructor
        $this->data = $data;
    }
    
    public function __get($name) {                // 2. Get undefined property
        return $this->data[$name] ?? null;
    }
    
    public function __set($name, $value) {        // 3. Set undefined property
        $this->data[$name] = $value;
    }
    
    public function __toString() {                // 4. Convert to string
        return json_encode($this->data);
    }
    
    public function __call($name, $args) {        // 5. Call undefined method
        echo "Called method: $name\n";
    }
}
```

### **Q9: What is Dependency Injection and why use it?**

**Answer:**
Dependency Injection is a design pattern where dependencies are provided to a class rather than the class creating them itself.

**Benefits:**
- **Loose Coupling** - Classes don't depend on concrete implementations
- **Testability** - Easy to mock dependencies
- **Flexibility** - Easy to change implementations
- **Single Responsibility** - Classes focus on their main purpose

```php
// Bad: Tight coupling
class UserService {
    private $database;
    
    public function __construct() {
        $this->database = new MySQLDatabase(); // Hard dependency
    }
}

// Good: Dependency injection
class UserService {
    private $database;
    
    public function __construct(DatabaseInterface $database) {
        $this->database = $database; // Injected dependency
    }
}

// Usage
$database = new MySQLDatabase();
$userService = new UserService($database); // Inject dependency
```

### **Q10: What is the difference between `include`, `require`, `include_once`, and `require_once`?**

**Answer:**

| Function | Error on Failure | Multiple Inclusions |
|----------|------------------|-------------------|
| `include` | Warning (continues) | Allowed |
| `require` | Fatal Error (stops) | Allowed |
| `include_once` | Warning (continues) | Prevented |
| `require_once` | Fatal Error (stops) | Prevented |

```php
// include - Warning if file not found, continues execution
include 'config.php';

// require - Fatal error if file not found, stops execution
require 'database.php';

// include_once - Include only once, prevents multiple inclusions
include_once 'functions.php';

// require_once - Require only once, most commonly used for classes
require_once 'User.php';
```

### **Q11: What is Autoloading in PHP?**

**Answer:**
Autoloading automatically loads class files when they are needed, without explicit `require` statements.

```php
// Simple autoloader
spl_autoload_register(function ($className) {
    $file = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// PSR-4 autoloader
spl_autoload_register(function ($className) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($className, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Now you can use classes without require
$user = new App\Models\User(); // Automatically loads the file
```

### **Q12: What is Late Static Binding?**

**Answer:**
Late Static Binding allows referencing the called class in the context of static inheritance using `static::` instead of `self::`.

```php
class A {
    public static function who() {
        echo "A\n";
    }
    
    public static function test() {
        self::who();   // Always calls A::who()
        static::who(); // Calls the actual class's who() method
    }
}

class B extends A {
    public static function who() {
        echo "B\n";
    }
}

B::test();
// Output:
// A (from self::who())
// B (from static::who())
```

### **Q13: What are Anonymous Classes?**

**Answer:**
Anonymous classes allow creation of one-off objects without defining a named class.

```php
// Anonymous class
$logger = new class {
    public function log($message) {
        echo "Log: $message\n";
    }
};

$logger->log("Test message");

// Anonymous class implementing interface
interface LoggerInterface {
    public function log($message);
}

function createLogger(): LoggerInterface {
    return new class implements LoggerInterface {
        public function log($message) {
            echo date('Y-m-d H:i:s') . ": $message\n";
        }
    };
}

$anonymousLogger = createLogger();
$anonymousLogger->log("Anonymous logger message");

// Anonymous class extending class
class BaseService {
    protected $name;
    
    public function getName() {
        return $this->name;
    }
}

$service = new class extends BaseService {
    public function __construct() {
        $this->name = "Anonymous Service";
    }
    
    public function process() {
        return "Processing with " . $this->getName();
    }
};

echo $service->process(); // Processing with Anonymous Service
```

### **Q14: What is Method Chaining?**

**Answer:**
Method chaining allows calling multiple methods on the same object in a single statement by returning `$this` or the object.

```php
class QueryBuilder {
    private $query = '';
    private $conditions = [];
    private $orderBy = '';
    private $limit = '';
    
    public function select($columns) {
        $this->query = "SELECT $columns";
        return $this; // Return self for chaining
    }
    
    public function from($table) {
        $this->query .= " FROM $table";
        return $this;
    }
    
    public function where($condition) {
        $this->conditions[] = $condition;
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }
    
    public function limit($count) {
        $this->limit = "LIMIT $count";
        return $this;
    }
    
    public function build() {
        $query = $this->query;
        
        if (!empty($this->conditions)) {
            $query .= " WHERE " . implode(' AND ', $this->conditions);
        }
        
        if ($this->orderBy) {
            $query .= " " . $this->orderBy;
        }
        
        if ($this->limit) {
            $query .= " " . $this->limit;
        }
        
        return $query;
    }
}

// Method chaining usage
$query = (new QueryBuilder())
    ->select('name, email')
    ->from('users')
    ->where('age > 18')
    ->where('status = "active"')
    ->orderBy('name')
    ->limit(10)
    ->build();

echo $query;
// SELECT name, email FROM users WHERE age > 18 AND status = "active" ORDER BY name ASC LIMIT 10
```

### **Q15: What is the Singleton Pattern?**

**Answer:**
Singleton ensures a class has only one instance and provides global access to it.

```php
class Singleton {
    private static $instance = null;
    private $data = [];
    
    // Private constructor prevents direct instantiation
    private function __construct() {
        // Initialize
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    private function __wakeup() {}
    
    public function setData($key, $value) {
        $this->data[$key] = $value;
    }
    
    public function getData($key) {
        return $this->data[$key] ?? null;
    }
}

// Usage
$singleton1 = Singleton::getInstance();
$singleton2 = Singleton::getInstance();

var_dump($singleton1 === $singleton2); // true (same instance)

$singleton1->setData('test', 'value');
echo $singleton2->getData('test'); // 'value' (shared data)
```

---

## Key Takeaways

### **When to Use Each Concept:**

1. **Classes & Objects** - Always, foundation of OOP
2. **Encapsulation** - Protect sensitive data, provide controlled access
3. **Inheritance** - "IS-A" relationships, code reuse
4. **Interfaces** - Contracts for unrelated classes, multiple inheritance
5. **Abstract Classes** - Partial implementation, related classes
6. **Traits** - Horizontal code reuse, avoid deep inheritance
7. **Static Members** - Shared data/functionality, utility functions
8. **Magic Methods** - Customize object behavior, operator overloading

### **Best Practices:**

1. **SOLID Principles** - Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
2. **Composition over Inheritance** - More flexible design
3. **Program to Interfaces** - Loose coupling
4. **Use Type Declarations** - Better code documentation and error prevention
5. **Meaningful Names** - Self-documenting code
6. **Keep Classes Focused** - Single responsibility
7. **Favor Dependency Injection** - Testable, flexible code

### **Common Mistakes to Avoid:**

1. **God Classes** - Classes that do too much
2. **Tight Coupling** - Hard dependencies between classes
3. **Deep Inheritance** - Too many inheritance levels
4. **Missing Access Modifiers** - Always specify visibility
5. **Not Using Interfaces** - Reduces flexibility
6. **Ignoring Error Handling** - Always handle exceptions
7. **Overusing Static** - Can make testing difficult

This comprehensive guide covers PHP OOP from basics to advanced concepts with practical examples and interview-ready explanations. Focus on understanding the "why" behind each concept, not just the "how"!
