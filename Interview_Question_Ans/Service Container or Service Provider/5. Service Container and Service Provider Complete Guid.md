<?php

/*
===============================================================================
DEPENDENCY INJECTION CONTAINER - সম্পূর্ণ নোট ও ব্যাখ্যা
===============================================================================

এই Container class টি একটি সম্পূর্ণ IoC (Inversion of Control) Container যা:
1. Services register করে
2. Dependencies automatically inject করে  
3. Singleton pattern support করে
4. Reflection ব্যবহার করে automatic object creation করে

কেন ব্যবহার করব?
------------------
✅ Loose Coupling - Classes একে অপরের উপর depend করে না
✅ Easy Testing - Mock objects easily inject করা যায়
✅ Maintainable Code - Code change করা সহজ
✅ SOLID Principles - Dependency Inversion Principle follow করে
✅ Automatic Dependency Resolution - Manual object creation লাগে না
*/

// ===============================================================================
// CONTAINER CLASS - DETAILED EXPLANATION
// ===============================================================================

class Container {
    /*
    PROPERTIES EXPLANATION:
    ----------------------
    $bindings - Interface/Abstract class কে Concrete class এর সাথে map করে
    $instances - Singleton objects store করে রাখে
    */
    private $bindings = [];    // ['LoggerInterface' => 'FileLogger']
    private $instances = [];   // ['LoggerInterface' => FileLoggerInstance]
    
    /*
    BIND METHOD:
    -----------
    Purpose: Interface কে Implementation এর সাথে bind করা
    
    Parameters:
    - $abstract: Interface বা Abstract class name
    - $concrete: Concrete implementation class বা Closure
    
    Logic:
    - $concrete null হলে $abstract কেই concrete হিসেবে use করে
    - $bindings array তে mapping store করে
    */
    public function bind(string $abstract, $concrete = null): void {
        echo "🔗 BINDING: $abstract";
        
        if ($concrete === null) {
            $concrete = $abstract;
            echo " -> $concrete (Same as abstract)\n";
        } else {
            echo " -> " . (is_string($concrete) ? $concrete : 'Closure') . "\n";
        }
        
        $this->bindings[$abstract] = $concrete;
        echo "📝 Stored in bindings array\n\n";
    }
    
    /*
    SINGLETON METHOD:
    ----------------
    Purpose: একটি class এর শুধুমাত্র একটি instance তৈরি করা
    
    Logic:
    - প্রথমে bind() method call করে
    - $instances array তে null value set করে (singleton flag হিসেবে)
    - resolve() এর সময় check করে singleton কিনা
    */
    public function singleton(string $abstract, $concrete = null): void {
        echo "🔒 SINGLETON BINDING: $abstract\n";
        
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null; // Singleton flag
        
        echo "🎯 Marked as singleton in instances array\n\n";
    }
    
    /*
    RESOLVE METHOD:
    --------------
    Purpose: Container থেকে object retrieve করা
    
    Algorithm:
    1. Singleton check করা
    2. Concrete class/closure পাওয়া
    3. Object build করা
    4. Singleton হলে store করা
    5. Object return করা
    */
    public function resolve(string $abstract) {
        echo "🔍 RESOLVING: $abstract\n";
        
        // STEP 1: Singleton instance check
        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            echo "✨ Returning existing singleton instance\n\n";
            return $this->instances[$abstract];
        }
        
        // STEP 2: Get concrete from bindings or use abstract itself
        $concrete = $this->bindings[$abstract] ?? $abstract;
        echo "🎯 Concrete found: " . (is_string($concrete) ? $concrete : 'Closure') . "\n";
        
        // STEP 3: Build object
        if ($concrete instanceof Closure) {
            echo "🏭 Creating object from Closure\n";
            $object = $concrete($this);
        } else {
            echo "🏗️ Building object using Reflection\n";
            $object = $this->build($concrete);
        }
        
        // STEP 4: Store singleton if marked
        if (isset($this->instances[$abstract])) {
            echo "💾 Storing as singleton instance\n";
            $this->instances[$abstract] = $object;
        }
        
        echo "✅ Object resolved successfully\n\n";
        return $object;
    }
    
    /*
    BUILD METHOD:
    ------------
    Purpose: Reflection ব্যবহার করে object তৈরি করা
    
    Algorithm:
    1. ReflectionClass তৈরি করা
    2. Instantiable check করা
    3. Constructor পাওয়া
    4. Constructor parameters resolve করা
    5. Object তৈরি করা
    */
    private function build(string $concrete) {
        echo "🔨 BUILDING: $concrete\n";
        
        // STEP 1: Create reflection class
        $reflection = new ReflectionClass($concrete);
        echo "🔍 Reflection created for: $concrete\n";
        
        // STEP 2: Check if class can be instantiated
        if (!$reflection->isInstantiable()) {
            throw new Exception("❌ Cannot instantiate $concrete (might be abstract or interface)");
        }
        echo "✅ Class is instantiable\n";
        
        // STEP 3: Get constructor
        $constructor = $reflection->getConstructor();
        
        // STEP 4: No constructor = simple instantiation
        if ($constructor === null) {
            echo "🎯 No constructor found, creating simple instance\n";
            return new $concrete;
        }
        
        echo "🔧 Constructor found, resolving dependencies...\n";
        
        // STEP 5: Resolve constructor dependencies
        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                echo "📦 Resolving dependency: $typeName\n";
                $dependencies[] = $this->resolve($typeName);
            } else {
                echo "⚠️ Skipping built-in type or no type hint\n";
                // Handle default values or primitive types
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependencies[] = null;
                }
            }
        }
        
        // STEP 6: Create instance with dependencies
        echo "🏭 Creating instance with " . count($dependencies) . " dependencies\n";
        return $reflection->newInstanceArgs($dependencies);
    }
}

// ===============================================================================
// EXAMPLE CLASSES - SHOWING DEPENDENCY INJECTION PATTERN
// ===============================================================================

/*
LOGGER INTERFACE:
----------------
Interface যা logging behavior define করে
*/
interface LoggerInterface {
    public function log(string $message): void;
}

/*
FILE LOGGER IMPLEMENTATION:
--------------------------
LoggerInterface এর concrete implementation
File এ log করার জন্য
*/
class FileLogger implements LoggerInterface {
    private $logFile;
    
    public function __construct(string $logFile = 'app.log') {
        $this->logFile = $logFile;
        echo "📁 FileLogger created - Log file: $logFile\n";
    }
    
    public function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        echo "📝 [FILE LOG] [$timestamp] $message\n";
        // Real implementation: file_put_contents($this->logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}

/*
DATABASE INTERFACE:
------------------
Database operations এর জন্য interface
*/
interface DatabaseInterface {
    public function save(array $data): bool;
    public function find(int $id): ?array;
}

/*
MYSQL DATABASE IMPLEMENTATION:
-----------------------------
DatabaseInterface এর MySQL implementation
*/
class MySQLDatabase implements DatabaseInterface {
    private $connection;
    
    public function __construct(string $host = 'localhost', string $database = 'app_db') {
        $this->connection = "mysql:host=$host;dbname=$database";
        echo "🗄️ MySQLDatabase created - Connection: $this->connection\n";
    }
    
    public function save(array $data): bool {
        echo "💾 [MYSQL] Saving data: " . json_encode($data) . "\n";
        // Real implementation: PDO query execution
        return true;
    }
    
    public function find(int $id): ?array {
        echo "🔍 [MYSQL] Finding record with ID: $id\n";
        // Real implementation: SELECT query
        return ['id' => $id, 'found' => true];
    }
}

/*
USER SERVICE:
------------
Business logic class যা Logger এবং Database depend করে
*/
class UserService {
    private $logger;
    private $database;
    
    /*
    CONSTRUCTOR DEPENDENCY INJECTION:
    --------------------------------
    Container automatically এই dependencies inject করবে
    */
    public function __construct(LoggerInterface $logger, DatabaseInterface $database) {
        $this->logger = $logger;
        $this->database = $database;
        echo "👤 UserService created with injected dependencies\n";
    }
    
    public function createUser(string $name, string $email): bool {
        // Log the action
        $this->logger->log("Attempting to create user: $name ($email)");
        
        // Prepare user data
        $userData = [
            'name' => $name, 
            'email' => $email, 
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Save to database
        $result = $this->database->save($userData);
        
        // Log result
        if ($result) {
            $this->logger->log("✅ User created successfully: $name");
        } else {
            $this->logger->log("❌ Failed to create user: $name");
        }
        
        return $result;
    }
    
    public function getUser(int $id): ?array {
        $this->logger->log("Fetching user with ID: $id");
        
        $user = $this->database->find($id);
        
        if ($user) {
            $this->logger->log("✅ User found: ID $id");
        } else {
            $this->logger->log("❌ User not found: ID $id");
        }
        
        return $user;
    }
}

// ===============================================================================
// ADVANCED EXAMPLES - DIFFERENT BINDING SCENARIOS
// ===============================================================================

/*
CLOSURE BINDING EXAMPLE:
-----------------------
Runtime এ complex object তৈরি করার জন্য
*/
class DatabaseLogger implements LoggerInterface {
    private $database;
    
    public function __construct(DatabaseInterface $database) {
        $this->database = $database;
        echo "🗃️ DatabaseLogger created\n";
    }
    
    public function log(string $message): void {
        $logData = [
            'message' => $message,
            'level' => 'info',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->database->save($logData);
        echo "📊 [DATABASE LOG] $message\n";
    }
}

/*
EMAIL SERVICE EXAMPLE:
---------------------
আরেকটি service যা dependency injection ব্যবহার করে
*/
interface EmailServiceInterface {
    public function send(string $to, string $subject, string $body): bool;
}

class SMTPEmailService implements EmailServiceInterface {
    private $logger;
    
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        echo "📧 SMTPEmailService created\n";
    }
    
    public function send(string $to, string $subject, string $body): bool {
        $this->logger->log("Sending email to: $to");
        
        echo "📤 [EMAIL] To: $to, Subject: $subject\n";
        echo "📄 Body: $body\n";
        
        $this->logger->log("✅ Email sent successfully to: $to");
        return true;
    }
}

/*
NOTIFICATION SERVICE:
--------------------
Multiple dependencies এর উদাহরণ
*/
class NotificationService {
    private $logger;
    private $emailService;
    private $database;
    
    public function __construct(
        LoggerInterface $logger, 
        EmailServiceInterface $emailService,
        DatabaseInterface $database
    ) {
        $this->logger = $logger;
        $this->emailService = $emailService;
        $this->database = $database;
        echo "🔔 NotificationService created with 3 dependencies\n";
    }
    
    public function sendWelcomeNotification(string $email, string $name): bool {
        $this->logger->log("Sending welcome notification to: $name");
        
        // Save notification record
        $notificationData = [
            'type' => 'welcome',
            'recipient' => $email,
            'sent_at' => date('Y-m-d H:i:s')
        ];
        $this->database->save($notificationData);
        
        // Send email
        $subject = "Welcome to our platform!";
        $body = "Hello $name, welcome to our amazing platform!";
        
        return $this->emailService->send($email, $subject, $body);
    }
}

// ===============================================================================
// CONTAINER USAGE EXAMPLES - DIFFERENT SCENARIOS
// ===============================================================================

echo "\n" . str_repeat("=", 80) . "\n";
echo "🚀 DEPENDENCY INJECTION CONTAINER DEMONSTRATION\n";
echo str_repeat("=", 80) . "\n\n";

// Create container instance
$container = new Container();

echo "SCENARIO 1: SIMPLE BINDING\n";
echo str_repeat("-", 40) . "\n";

// Bind interfaces to implementations
$container->bind(LoggerInterface::class, FileLogger::class);
$container->bind(DatabaseInterface::class, MySQLDatabase::class);
$container->bind(UserService::class);

// Resolve and use
$userService = $container->resolve(UserService::class);
$userService->createUser('আহমেদ আলী', 'ahmed@example.com');

echo "\n\nSCENARIO 2: SINGLETON BINDING\n";
echo str_repeat("-", 40) . "\n";

// Create new container for singleton demo
$singletonContainer = new Container();
$singletonContainer->singleton(LoggerInterface::class, FileLogger::class);
$singletonContainer->bind(DatabaseInterface::class, MySQLDatabase::class);

// Resolve multiple times - same logger instance
$service1 = $singletonContainer->resolve(LoggerInterface::class);
$service2 = $singletonContainer->resolve(LoggerInterface::class);

echo "Same instance? " . ($service1 === $service2 ? "✅ YES" : "❌ NO") . "\n";

echo "\n\nSCENARIO 3: CLOSURE BINDING\n";
echo str_repeat("-", 40) . "\n";

$closureContainer = new Container();

// Bind with closure for custom instantiation
$closureContainer->bind(LoggerInterface::class, function($container) {
    echo "🔧 Custom instantiation through closure\n";
    $database = $container->resolve(DatabaseInterface::class);
    return new DatabaseLogger($database);
});

$closureContainer->bind(DatabaseInterface::class, MySQLDatabase::class);

$customLogger = $closureContainer->resolve(LoggerInterface::class);
$customLogger->log('This is logged to database!');

echo "\n\nSCENARIO 4: COMPLEX DEPENDENCY CHAIN\n";
echo str_repeat("-", 40) . "\n";

$complexContainer = new Container();

// Bind all services
$complexContainer->bind(LoggerInterface::class, FileLogger::class);
$complexContainer->bind(DatabaseInterface::class, MySQLDatabase::class);
$complexContainer->bind(EmailServiceInterface::class, SMTPEmailService::class);
$complexContainer->bind(NotificationService::class);

// Container will automatically resolve all dependencies
$notificationService = $complexContainer->resolve(NotificationService::class);
$notificationService->sendWelcomeNotification('user@example.com', 'নতুন ব্যবহারকারী');

// ===============================================================================
// TESTING EXAMPLE - MOCK OBJECTS
// ===============================================================================

echo "\n\nSCENARIO 5: TESTING WITH MOCK OBJECTS\n";
echo str_repeat("-", 40) . "\n";

/*
MOCK LOGGER FOR TESTING:
-----------------------
Testing এর সময় real file operations না করে mock ব্যবহার করি
*/
class MockLogger implements LoggerInterface {
    public $logs = [];
    
    public function log(string $message): void {
        $this->logs[] = $message;
        echo "🧪 [MOCK LOG] $message\n";
    }
    
    public function getLogCount(): int {
        return count($this->logs);
    }
}

/*
MOCK DATABASE FOR TESTING:
--------------------------
Testing এর সময় real database operations না করে mock ব্যবহার করি
*/
class MockDatabase implements DatabaseInterface {
    public $savedData = [];
    
    public function save(array $data): bool {
        $this->savedData[] = $data;
        echo "🧪 [MOCK DB] Data saved: " . json_encode($data) . "\n";
        return true;
    }
    
    public function find(int $id): ?array {
        echo "🧪 [MOCK DB] Finding ID: $id\n";
        return ['id' => $id, 'mock' => true];
    }
    
    public function getSavedDataCount(): int {
        return count($this->savedData);
    }
}

// Testing container setup
$testContainer = new Container();
$testContainer->bind(LoggerInterface::class, MockLogger::class);
$testContainer->bind(DatabaseInterface::class, MockDatabase::class);
$testContainer->bind(UserService::class);

// Test user creation
$testUserService = $testContainer->resolve(UserService::class);
$result = $testUserService->createUser('Test User', 'test@example.com');

// Verify test results
$mockLogger = $testContainer->resolve(LoggerInterface::class);
$mockDatabase = $testContainer->resolve(DatabaseInterface::class);

echo "\n📊 TEST RESULTS:\n";
echo "✅ User creation result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
echo "📝 Log entries: " . $mockLogger->getLogCount() . "\n";
echo "💾 Database saves: " . $mockDatabase->getSavedDataCount() . "\n";

// ===============================================================================
// PERFORMANCE COMPARISON - WITH VS WITHOUT CONTAINER
// ===============================================================================

echo "\n\nSCENARIO 6: PERFORMANCE & FLEXIBILITY COMPARISON\n";
echo str_repeat("-", 50) . "\n";

// WITHOUT CONTAINER (Tightly Coupled)
class TightlyCoupledUserService {
    private $logger;
    private $database;
    
    public function __construct() {
        // Hard-coded dependencies - Bad practice!
        $this->logger = new FileLogger();
        $this->database = new MySQLDatabase();
        echo "❌ Tightly coupled service created\n";
    }
    
    public function createUser(string $name, string $email): bool {
        $this->logger->log("Creating user: $name");
        return $this->database->save(['name' => $name, 'email' => $email]);
    }
}

// WITH CONTAINER (Loosely Coupled)
$flexibleContainer = new Container();
$flexibleContainer->bind(LoggerInterface::class, FileLogger::class);
$flexibleContainer->bind(DatabaseInterface::class, MySQLDatabase::class);
$flexibleContainer->bind(UserService::class);

echo "\n🔗 TIGHTLY COUPLED APPROACH:\n";
$tightService = new TightlyCoupledUserService();
$tightService->createUser('Tight User', 'tight@example.com');

echo "\n🔄 LOOSELY COUPLED APPROACH:\n";
$looseService = $flexibleContainer->resolve(UserService::class);
$looseService->createUser('Loose User', 'loose@example.com');

echo "\n💡 BENEFITS OF CONTAINER APPROACH:\n";
echo "✅ Easy to change implementations\n";
echo "✅ Easy to test with mocks\n";
echo "✅ Follows SOLID principles\n";
echo "✅ Automatic dependency resolution\n";
echo "✅ Singleton support\n";
echo "✅ Centralized configuration\n";

// ===============================================================================
// ERROR HANDLING EXAMPLES
// ===============================================================================

echo "\n\nSCENARIO 7: ERROR HANDLING\n";
echo str_repeat("-", 30) . "\n";

$errorContainer = new Container();

try {
    // Try to resolve non-existent class
    echo "🚫 Attempting to resolve non-existent class...\n";
    $errorContainer->resolve('NonExistentClass');
} catch (Exception $e) {
    echo "❌ ERROR CAUGHT: " . $e->getMessage() . "\n";
}

// Try to instantiate interface directly
abstract class AbstractClass {
    abstract public function doSomething();
}

$errorContainer->bind('AbstractTest', AbstractClass::class);

try {
    echo "\n🚫 Attempting to instantiate abstract class...\n";
    $errorContainer->resolve('AbstractTest');
} catch (Exception $e) {
    echo "❌ ERROR CAUGHT: " . $e->getMessage() . "\n";
}

// ===============================================================================
// SUMMARY & BEST PRACTICES
// ===============================================================================

echo "\n" . str_repeat("=", 80) . "\n";
echo "📚 SUMMARY & BEST PRACTICES\n";
echo str_repeat("=", 80) . "\n";

echo "
🎯 KEY CONCEPTS LEARNED:
------------------------
1. Container Class Structure:
   - bindings[] : Interface to Implementation mapping
   - instances[] : Singleton objects storage
   
2. Core Methods:
   - bind() : Register services
   - singleton() : Register singleton services  
   - resolve() : Get services from container
   - build() : Create objects using Reflection
   
3. Dependency Resolution Algorithm:
   - Check singleton cache
   - Get concrete from bindings
   - Build object with dependencies
   - Store singleton if needed
   - Return object

🏆 BEST PRACTICES:
-----------------
✅ Always code to interfaces, not concrete classes
✅ Use constructor injection for required dependencies
✅ Keep constructors simple - just assign dependencies
✅ Use singletons for expensive-to-create objects
✅ Use mocks for testing
✅ Handle errors gracefully
✅ Keep container configuration centralized

🚀 ADVANTAGES OF THIS APPROACH:
------------------------------
✅ Loose Coupling - Easy to change implementations
✅ Testability - Easy to inject mocks
✅ Maintainability - Clean separation of concerns
✅ Flexibility - Runtime dependency configuration
✅ Performance - Singleton support for heavy objects
✅ Automatic Resolution - No manual object creation

⚠️ THINGS TO WATCH OUT FOR:
---------------------------
❌ Circular dependencies can cause infinite loops
❌ Too many dependencies might indicate bad design
❌ Reflection has slight performance overhead
❌ Type hints are required for automatic resolution
❌ Abstract classes/interfaces cannot be instantiated directly

🎊 CONCLUSION:
-------------
এই Container implementation Laravel এর Service Container এর একটি simplified version।
এটি দিয়ে আমরা professional level dependency injection করতে পারি যা:
- Code quality improve করে
- Testing সহজ করে  
- Maintenance cost কমায়
- SOLID principles follow করে
";

echo "\n🎉 DEPENDENCY INJECTION CONTAINER TUTORIAL COMPLETE! 🎉\n";
echo str_repeat("=", 80) . "\n";

/*
===============================================================================
FINAL NOTES:
===============================================================================

এই Container class এর মাধ্যমে আমরা শিখলাম:

1. IoC Container কিভাবে কাজ করে
2. Dependency Injection এর সুবিধা
3. Reflection ব্যবহার করে automatic object creation
4. Singleton pattern implementation
5. Testing এর জন্য Mock objects ব্যবহার
6. Error handling এবং edge cases
7. Performance considerations
8. Best practices এবং common pitfalls

এটি Laravel এর Service Container এর foundation। Laravel এ আরো advanced 
features আছে যেমন:
- Contextual binding
- Tagging
- Method injection  
- Service providers
- Facades

কিন্তু মূল concept এই Container class এই আছে।
===============================================================================
*/
