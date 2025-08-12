<?php

// ====================================================================
// 1. SINGLE RESPONSIBILITY PRINCIPLE (SRP)
// "A class should have only one reason to change"
// ====================================================================

// ❌ BAD: User class doing too many things
class BadUser {
    public $name;
    public $email;
    
    public function save() {
        // Database logic here - WRONG!
        echo "Saving user to database...\n";
    }
    
    public function sendEmail() {
        // Email logic here - WRONG!
        echo "Sending email to user...\n";
    }
}

// ✅ GOOD: Each class has single responsibility
class User {
    public $name;
    public $email;
    
    public function __construct($name, $email) {
        $this->name = $name;
        $this->email = $email;
    }
}

class UserRepository {
    public function save(User $user) {
        echo "Saving {$user->name} to database...\n";
    }
}

class EmailService {
    public function sendWelcomeEmail(User $user) {
        echo "Sending welcome email to {$user->email}...\n";
    }
}

// ====================================================================
// 2. OPEN/CLOSED PRINCIPLE (OCP)
// "Open for extension, closed for modification"
// ====================================================================

// ✅ GOOD: Base shape class
abstract class Shape {
    abstract public function calculateArea();
}

// ✅ GOOD: We can add new shapes without modifying existing code
class Circle extends Shape {
    private $radius;
    
    public function __construct($radius) {
        $this->radius = $radius;
    }
    
    public function calculateArea() {
        return pi() * $this->radius * $this->radius;
    }
}

class Rectangle extends Shape {
    private $width;
    private $height;
    
    public function __construct($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }
    
    public function calculateArea() {
        return $this->width * $this->height;
    }
}

class AreaCalculator {
    public function calculateTotalArea(array $shapes) {
        $total = 0;
        foreach ($shapes as $shape) {
            $total += $shape->calculateArea(); // Works with any Shape!
        }
        return $total;
    }
}

// ====================================================================
// 3. LISKOV SUBSTITUTION PRINCIPLE (LSP)
// "Objects should be replaceable with instances of their subtypes"
// ====================================================================

// ✅ GOOD: Base class
class Bird {
    public function eat() {
        echo "Bird is eating...\n";
    }
}

// ✅ GOOD: Flying birds
class FlyingBird extends Bird {
    public function fly() {
        echo "Bird is flying...\n";
    }
}

// ✅ GOOD: Sparrow can substitute FlyingBird
class Sparrow extends FlyingBird {
    public function fly() {
        echo "Sparrow is flying fast...\n";
    }
}

// ✅ GOOD: Penguin doesn't inherit fly() - correct design
class Penguin extends Bird {
    public function swim() {
        echo "Penguin is swimming...\n";
    }
}

// ====================================================================
// 4. INTERFACE SEGREGATION PRINCIPLE (ISP)
// "Don't force classes to implement interfaces they don't use"
// ====================================================================

// ✅ GOOD: Small, specific interfaces
interface Readable {
    public function read();
}

interface Writable {
    public function write($data);
}

interface Executable {
    public function execute();
}

// ✅ GOOD: Classes implement only what they need
class TextFile implements Readable, Writable {
    private $content = "";
    
    public function read() {
        return $this->content;
    }
    
    public function write($data) {
        $this->content = $data;
        echo "Writing to text file: {$data}\n";
    }
}

class VideoFile implements Readable {
    public function read() {
        echo "Reading video file...\n";
        return "video_data";
    }
}

class Script implements Readable, Executable {
    public function read() {
        echo "Reading script file...\n";
        return "script_content";
    }
    
    public function execute() {
        echo "Executing script...\n";
    }
}

// ====================================================================
// 5. DEPENDENCY INVERSION PRINCIPLE (DIP)
// "Depend on abstractions, not concretions"
// ====================================================================

// ✅ GOOD: Interface (abstraction)
interface DatabaseInterface {
    public function save($data);
    public function find($id);
}

// ✅ GOOD: Concrete implementations
class MySQLDatabase implements DatabaseInterface {
    public function save($data) {
        echo "Saving to MySQL: {$data}\n";
    }
    
    public function find($id) {
        echo "Finding from MySQL ID: {$id}\n";
        return "mysql_data";
    }
}

class MongoDatabase implements DatabaseInterface {
    public function save($data) {
        echo "Saving to MongoDB: {$data}\n";
    }
    
    public function find($id) {
        echo "Finding from MongoDB ID: {$id}\n";
        return "mongo_data";
    }
}

// ✅ GOOD: Service depends on abstraction, not concrete class
class UserService {
    private $database;
    
    public function __construct(DatabaseInterface $database) {
        $this->database = $database; // Depends on interface!
    }
    
    public function saveUser($userData) {
        $this->database->save($userData);
    }
    
    public function getUser($id) {
        return $this->database->find($id);
    }
}

// ====================================================================
// DEMONSTRATION: Using all principles together
// ====================================================================

echo "=== SOLID Principles Demo ===\n\n";

// 1. Single Responsibility
echo "1. Single Responsibility:\n";
$user = new User("John Doe", "john@example.com");
$userRepo = new UserRepository();
$emailService = new EmailService();

$userRepo->save($user);
$emailService->sendWelcomeEmail($user);
echo "\n";

// 2. Open/Closed
echo "2. Open/Closed:\n";
$shapes = [
    new Circle(5),
    new Rectangle(4, 6)
];
$calculator = new AreaCalculator();
echo "Total area: " . $calculator->calculateTotalArea($shapes) . "\n\n";

// 3. Liskov Substitution
echo "3. Liskov Substitution:\n";
$birds = [new Sparrow(), new Penguin()];
foreach ($birds as $bird) {
    $bird->eat(); // All birds can eat
    if ($bird instanceof FlyingBird) {
        $bird->fly(); // Only flying birds can fly
    }
}
echo "\n";

// 4. Interface Segregation
echo "4. Interface Segregation:\n";
$textFile = new TextFile();
$textFile->write("Hello World");
echo $textFile->read() . "\n";

$script = new Script();
$script->read();
$script->execute();
echo "\n";

// 5. Dependency Inversion
echo "5. Dependency Inversion:\n";
$mysqlService = new UserService(new MySQLDatabase());
$mongoService = new UserService(new MongoDatabase());

$mysqlService->saveUser("User data");
$mongoService->saveUser("User data");
echo "\n";

?>
