# Design Patterns in PHP - Complete Guide

## 1. Singleton Pattern
**Definition:** Create only one object from a class and provide that object to others (who call for object).

**Real-life Use Cases:** Log writing, Database connection, Configuration management, Cache management

```php
<?php
class Logger 
{
    private static $instance = null;
    private $logFile;
    
    // Private constructor to prevent direct instantiation
    private function __construct() 
    {
        $this->logFile = 'app.log';
    }
    
    // Get the single instance
    public static function getInstance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($message) 
    {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    private function __wakeup() {}
}

// Usage
$logger1 = Logger::getInstance();
$logger2 = Logger::getInstance();

var_dump($logger1 === $logger2); // true - same instance

$logger1->log("User logged in");
$logger2->log("Product purchased"); // Both use same log file
?>
```

## 2. Factory Method Pattern
**Definition:** A Factory for providing objects from a class. The class provides objects to anyone who calls for an object. It's a normal class that creates objects.

**Real-life Use Cases:** Creating different payment processors, Database drivers, Notification services

```php
<?php
// Product interface
interface PaymentProcessor 
{
    public function processPayment($amount);
}

// Concrete products
class PayPalProcessor implements PaymentProcessor 
{
    public function processPayment($amount) 
    {
        return "Processing $amount via PayPal";
    }
}

class StripeProcessor implements PaymentProcessor 
{
    public function processPayment($amount) 
    {
        return "Processing $amount via Stripe";
    }
}

class CreditCardProcessor implements PaymentProcessor 
{
    public function processPayment($amount) 
    {
        return "Processing $amount via Credit Card";
    }
}

// Factory
class PaymentFactory 
{
    public static function createProcessor($type) 
    {
        switch (strtolower($type)) {
            case 'paypal':
                return new PayPalProcessor();
            case 'stripe':
                return new StripeProcessor();
            case 'creditcard':
                return new CreditCardProcessor();
            default:
                throw new Exception("Unknown payment type: $type");
        }
    }
}

// Usage
$paymentType = 'paypal'; // Could come from user input
$processor = PaymentFactory::createProcessor($paymentType);
echo $processor->processPayment(100); // "Processing 100 via PayPal"
?>
```

## 3. Abstract Factory Method Pattern
**Definition:** A collection of Factory methods. Provides an interface for creating families of related objects.

**Real-life Use Cases:** Cross-platform UI components, Different database systems with multiple related classes

```php
<?php
// Abstract products
interface Button 
{
    public function render();
}

interface Checkbox 
{
    public function render();
}

// Concrete products for Windows
class WindowsButton implements Button 
{
    public function render() 
    {
        return "Rendering Windows-style button";
    }
}

class WindowsCheckbox implements Checkbox 
{
    public function render() 
    {
        return "Rendering Windows-style checkbox";
    }
}

// Concrete products for Mac
class MacButton implements Button 
{
    public function render() 
    {
        return "Rendering Mac-style button";
    }
}

class MacCheckbox implements Checkbox 
{
    public function render() 
    {
        return "Rendering Mac-style checkbox";
    }
}

// Abstract factory
interface GUIFactory 
{
    public function createButton(): Button;
    public function createCheckbox(): Checkbox;
}

// Concrete factories
class WindowsFactory implements GUIFactory 
{
    public function createButton(): Button 
    {
        return new WindowsButton();
    }
    
    public function createCheckbox(): Checkbox 
    {
        return new WindowsCheckbox();
    }
}

class MacFactory implements GUIFactory 
{
    public function createButton(): Button 
    {
        return new MacButton();
    }
    
    public function createCheckbox(): Checkbox 
    {
        return new MacCheckbox();
    }
}

// Usage
$os = 'windows'; // Could be detected automatically
$factory = ($os === 'windows') ? new WindowsFactory() : new MacFactory();

$button = $factory->createButton();
$checkbox = $factory->createCheckbox();

echo $button->render() . "\n";     // "Rendering Windows-style button"
echo $checkbox->render() . "\n";   // "Rendering Windows-style checkbox"
?>
```

## 4. Builder Method Pattern
**Definition:** A class used for assigning object properties step by step. Like in Axios where you set header, URL, body, method - Builder class assigns those step by step.

**Real-life Use Cases:** HTTP clients, SQL query builders, Email builders, Configuration objects

```php
<?php
class HttpRequest 
{
    private $url;
    private $method = 'GET';
    private $headers = [];
    private $body;
    private $timeout = 30;
    
    public function getUrl() { return $this->url; }
    public function getMethod() { return $this->method; }
    public function getHeaders() { return $this->headers; }
    public function getBody() { return $this->body; }
    public function getTimeout() { return $this->timeout; }
}

class HttpRequestBuilder 
{
    private $request;
    
    public function __construct() 
    {
        $this->request = new HttpRequest();
    }
    
    public function url($url) 
    {
        $this->request->url = $url;
        return $this; // Return self for method chaining
    }
    
    public function method($method) 
    {
        $this->request->method = strtoupper($method);
        return $this;
    }
    
    public function header($key, $value) 
    {
        $this->request->headers[$key] = $value;
        return $this;
    }
    
    public function body($data) 
    {
        $this->request->body = json_encode($data);
        return $this;
    }
    
    public function timeout($seconds) 
    {
        $this->request->timeout = $seconds;
        return $this;
    }
    
    public function build() 
    {
        return $this->request;
    }
}

// Usage - Similar to Axios configuration
$request = (new HttpRequestBuilder())
    ->url('https://api.example.com/users')
    ->method('POST')
    ->header('Content-Type', 'application/json')
    ->header('Authorization', 'Bearer token123')
    ->body(['name' => 'John', 'email' => 'john@example.com'])
    ->timeout(60)
    ->build();

echo "URL: " . $request->getUrl() . "\n";
echo "Method: " . $request->getMethod() . "\n";
echo "Headers: " . json_encode($request->getHeaders()) . "\n";
?>
```

## 5. Prototype Method Pattern
**Definition:** Clone object, not copy the object. Creates new instances by copying existing instances.

**Real-life Use Cases:** Document templates, Game object creation, Configuration presets

```php
<?php
class Document 
{
    private $title;
    private $content;
    private $metadata;
    
    public function __construct($title, $content) 
    {
        $this->title = $title;
        $this->content = $content;
        $this->metadata = [
            'created_at' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ];
    }
    
    public function setTitle($title) 
    {
        $this->title = $title;
    }
    
    public function setContent($content) 
    {
        $this->content = $content;
    }
    
    public function getTitle() { return $this->title; }
    public function getContent() { return $this->content; }
    public function getMetadata() { return $this->metadata; }
    
    // Implement cloning
    public function __clone() 
    {
        // Deep clone metadata array
        $this->metadata = array_merge([], $this->metadata);
        $this->metadata['created_at'] = date('Y-m-d H:i:s');
        $this->metadata['version'] = '1.0';
    }
    
    public function createCopy() 
    {
        return clone $this;
    }
}

// Usage
$template = new Document("Template Document", "This is a template content");

// Clone the template to create new documents
$doc1 = $template->createCopy();
$doc1->setTitle("User Manual");
$doc1->setContent("How to use our software...");

$doc2 = $template->createCopy();
$doc2->setTitle("API Documentation");
$doc2->setContent("API endpoints and usage...");

echo "Template: " . $template->getTitle() . "\n";
echo "Doc1: " . $doc1->getTitle() . "\n";
echo "Doc2: " . $doc2->getTitle() . "\n";

// Each has its own metadata
var_dump($doc1->getMetadata());
var_dump($doc2->getMetadata());
?>
```

## 6. Deep Copy vs Shallow Copy
**Definition:** 
- **Shallow Copy:** Copy an object but properties that use references/pointers still point to the same memory location
- **Deep Copy:** Copy object completely including all referenced/nested objects

**Real-life Use Cases:** Copying complex data structures, User profiles with nested objects, Shopping cart items

```php
<?php
class Address 
{
    public $street;
    public $city;
    public $country;
    
    public function __construct($street, $city, $country) 
    {
        $this->street = $street;
        $this->city = $city;
        $this->country = $country;
    }
}

class User 
{
    public $name;
    public $email;
    public $address; // This is an object reference
    public $hobbies; // This is an array
    
    public function __construct($name, $email, Address $address, array $hobbies) 
    {
        $this->name = $name;
        $this->email = $email;
        $this->address = $address;
        $this->hobbies = $hobbies;
    }
    
    // Shallow copy (default PHP behavior)
    public function shallowCopy() 
    {
        return clone $this;
    }
    
    // Deep copy implementation
    public function __clone() 
    {
        // Deep clone the address object
        $this->address = clone $this->address;
        
        // Deep clone the hobbies array (if it contained objects, we'd clone those too)
        $this->hobbies = array_merge([], $this->hobbies);
    }
    
    public function deepCopy() 
    {
        return clone $this;
    }
}

// Usage example
$address = new Address("123 Main St", "New York", "USA");
$originalUser = new User("John Doe", "john@example.com", $address, ["reading", "coding"]);

// Shallow copy demonstration (without proper __clone implementation)
class UserShallow extends User 
{
    public function __clone() 
    {
        // Intentionally do nothing for shallow copy demo
    }
}

$shallowUser = new UserShallow("John Doe", "john@example.com", $address, ["reading", "coding"]);
$shallowCopy = clone $shallowUser;

// Change address in shallow copy
$shallowCopy->address->street = "456 Oak Ave";
echo "Original address: " . $shallowUser->address->street . "\n"; // "456 Oak Ave" - MODIFIED!
echo "Shallow copy address: " . $shallowCopy->address->street . "\n"; // "456 Oak Ave"

echo "\n--- Deep Copy Example ---\n";

// Deep copy demonstration
$deepCopy = $originalUser->deepCopy();

// Change address in deep copy
$deepCopy->address->street = "789 Pine St";
$deepCopy->hobbies[] = "swimming";

echo "Original address: " . $originalUser->address->street . "\n"; // "123 Main St" - UNCHANGED
echo "Deep copy address: " . $deepCopy->address->street . "\n";     // "789 Pine St"

echo "Original hobbies: " . implode(", ", $originalUser->hobbies) . "\n"; // "reading, coding"
echo "Deep copy hobbies: " . implode(", ", $deepCopy->hobbies) . "\n";     // "reading, coding, swimming"
?>
```

## Summary

These design patterns solve common programming problems:

1. **Singleton** - Ensures single instance (Logger, DB connection)
2. **Factory Method** - Creates objects without specifying exact classes (Payment processors)
3. **Abstract Factory** - Creates families of related objects (UI components for different platforms)
4. **Builder** - Constructs complex objects step by step (HTTP requests, SQL queries)
5. **Prototype** - Creates objects by cloning existing ones (Document templates)
6. **Deep vs Shallow Copy** - Controls how object references are handled during copying

Each pattern addresses specific scenarios in real-world applications, making code more maintainable, flexible, and reusable.
