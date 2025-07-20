<?php

// =====================================================
// COMPLETE LARAVEL FACADE PATTERN TUTORIAL
// =====================================================

// STEP 1: CREATE THE ACTUAL SERVICE CLASS
// ----------------------------------------
// File: app/Services/Calculator.php

namespace App\Services;

/**
 * This is the REAL class that does the actual work
 * It contains all the business logic
 */
class Calculator
{
    private $result = 0;
    
    public function add($a, $b = null)
    {
        if ($b === null) {
            $this->result += $a;
            return $this;
        }
        return $a + $b;
    }
    
    public function subtract($a, $b = null)
    {
        if ($b === null) {
            $this->result -= $a;
            return $this;
        }
        return $a - $b;
    }
    
    public function multiply($a, $b)
    {
        return $a * $b;
    }
    
    public function divide($a, $b)
    {
        if ($b == 0) {
            throw new \Exception("Division by zero!");
        }
        return $a / $b;
    }
    
    public function getResult()
    {
        return $this->result;
    }
    
    public function reset()
    {
        $this->result = 0;
        return $this;
    }
}

// =====================================================
// STEP 2: CREATE THE FACADE CLASS
// ----------------------------------------
// File: app/Facades/Calculator.php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * This is just a "pointer" to the real Calculator service
 * It has NO actual methods - it's just a bridge
 */
class Calculator extends Facade
{
    /**
     * This method is CRUCIAL - it tells Laravel which service to use
     * 
     * @return string The key that's registered in the service container
     */
    protected static function getFacadeAccessor()
    {
        return 'calculator';  // This must match the container binding
    }
}

// =====================================================
// STEP 3: CREATE SERVICE PROVIDER
// ----------------------------------------
// File: app/Providers/CalculatorServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Calculator;

/**
 * This class registers our Calculator service in Laravel's container
 */
class CalculatorServiceProvider extends ServiceProvider
{
    /**
     * Register the service in the container
     */
    public function register()
    {
        // Method 1: Simple binding
        $this->app->bind('calculator', function ($app) {
            return new Calculator();
        });
        
        // Method 2: Singleton (same instance always)
        // $this->app->singleton('calculator', function ($app) {
        //     return new Calculator();
        // });
        
        // Method 3: Bind interface to implementation
        // $this->app->bind(CalculatorInterface::class, Calculator::class);
        // $this->app->bind('calculator', CalculatorInterface::class);
    }
    
    /**
     * Bootstrap any services (optional)
     */
    public function boot()
    {
        // You can publish config files, load routes, etc. here
    }
}

// =====================================================
// STEP 4: REGISTER THE SERVICE PROVIDER
// ----------------------------------------
// File: config/app.php

return [
    // ... other config
    
    'providers' => [
        // ... other providers
        
        /*
         * Application Service Providers...
         */
        App\Providers\CalculatorServiceProvider::class,
    ],
    
    'aliases' => [
        // ... other aliases
        
        'Calculator' => App\Facades\Calculator::class,
    ],
];

// =====================================================
// STEP 5: HOW TO USE THE FACADE
// ----------------------------------------
// File: app/Http/Controllers/TestController.php

namespace App\Http\Controllers;

use Calculator;  // Using the alias from config/app.php
// OR
// use App\Facades\Calculator;

class TestController extends Controller
{
    public function testCalculator()
    {
        // Using the facade
        $sum = Calculator::add(5, 3);        // Returns: 8
        $diff = Calculator::subtract(10, 4);  // Returns: 6
        $product = Calculator::multiply(3, 7); // Returns: 21
        $quotient = Calculator::divide(20, 4); // Returns: 5
        
        // Method chaining example
        $result = Calculator::reset()
            ->add(10)
            ->add(5)
            ->subtract(3)
            ->getResult(); // Returns: 12
        
        return response()->json([
            'sum' => $sum,
            'difference' => $diff,
            'product' => $product,
            'quotient' => $quotient,
            'chained_result' => $result
        ]);
    }
}

// =====================================================
// UNDERSTANDING THE FLOW - WHAT HAPPENS INTERNALLY
// =====================================================

/**
 * When you call: Calculator::add(5, 3)
 * 
 * Here's what happens step by step:
 */

// STEP 1: PHP looks for static method 'add' in Calculator facade
// Result: Not found! The facade has no 'add' method

// STEP 2: PHP triggers __callStatic magic method
// This is inherited from Laravel's base Facade class

// SIMPLIFIED VERSION OF LARAVEL'S FACADE BASE CLASS:
namespace Illuminate\Support\Facades;

abstract class Facade
{
    /**
     * Handle dynamic, static calls to the object.
     */
    public static function __callStatic($method, $args)
    {
        // Get the instance from the container
        $instance = static::getFacadeRoot();
        
        // Call the method on the real instance
        return $instance->$method(...$args);
    }
    
    /**
     * Get the root object behind the facade.
     */
    public static function getFacadeRoot()
    {
        // Get the key from the child facade
        $name = static::getFacadeAccessor();
        
        // Resolve the instance from container
        return static::resolveFacadeInstance($name);
    }
    
    /**
     * Resolve the facade instance from the container
     */
    protected static function resolveFacadeInstance($name)
    {
        // Get the instance from Laravel's service container
        return app($name);
    }
    
    /**
     * Each facade must implement this method
     */
    abstract protected static function getFacadeAccessor();
}

// =====================================================
// THE COMPLETE FLOW VISUALIZED
// =====================================================

/*
Calculator::add(5, 3)
    │
    ├─► Step 1: PHP checks Calculator facade for static 'add' method
    │           Result: Not found!
    │
    ├─► Step 2: PHP calls __callStatic('add', [5, 3])
    │           (Inherited from Facade base class)
    │
    └─► Step 3: __callStatic executes:
            │
            ├─► 3a: $instance = static::getFacadeRoot()
            │       │
            │       ├─► getFacadeAccessor() returns 'calculator'
            │       │
            │       └─► app('calculator') returns Calculator service instance
            │
            └─► 3b: return $instance->add(5, 3)
                    │
                    └─► Calls add() on real Calculator service
                        Returns: 8
*/

// =====================================================
// SERVICE CONTAINER EXPLAINED
// =====================================================

/**
 * The service container is like a big array of services:
 */
class SimpleContainer
{
    protected $bindings = [];
    
    // Register a service
    public function bind($key, $resolver)
    {
        $this->bindings[$key] = $resolver;
    }
    
    // Get a service
    public function make($key)
    {
        if (isset($this->bindings[$key])) {
            $resolver = $this->bindings[$key];
            
            // If it's a closure, execute it
            if ($resolver instanceof \Closure) {
                return $resolver($this);
            }
            
            // If it's a class name, instantiate it
            if (is_string($resolver)) {
                return new $resolver;
            }
            
            return $resolver;
        }
        
        throw new \Exception("Service {$key} not found in container");
    }
}

// Usage example:
$container = new SimpleContainer();

// Register the calculator
$container->bind('calculator', function() {
    return new \App\Services\Calculator();
});

// Retrieve it
$calc = $container->make('calculator');
$result = $calc->add(5, 3); // Returns: 8

// =====================================================
// COMPLETE WORKING EXAMPLE (STANDALONE)
// =====================================================

// 1. The Service
class CalculatorService {
    public function add($a, $b) { return $a + $b; }
    public function subtract($a, $b) { return $a - $b; }
}

// 2. The Container (simplified)
class Container {
    private static $services = [];
    
    public static function bind($key, $service) {
        self::$services[$key] = $service;
    }
    
    public static function get($key) {
        return self::$services[$key];
    }
}

// 3. Base Facade
abstract class BaseFacade {
    public static function __callStatic($method, $args) {
        $key = static::getFacadeAccessor();
        $instance = Container::get($key);
        return $instance->$method(...$args);
    }
    
    abstract protected static function getFacadeAccessor();
}

// 4. Calculator Facade
class CalcFacade extends BaseFacade {
    protected static function getFacadeAccessor() {
        return 'calc';
    }
}

// 5. Setup
Container::bind('calc', new CalculatorService());

// 6. Usage
echo CalcFacade::add(10, 5);      // Output: 15
echo CalcFacade::subtract(10, 3); // Output: 7

// =====================================================
// KEY CONCEPTS SUMMARY
// =====================================================

/**
 * 1. FACADE CLASS:
 *    - Has NO real methods
 *    - Just returns a key via getFacadeAccessor()
 *    - Inherits __callStatic from base Facade
 * 
 * 2. getFacadeAccessor():
 *    - Returns a string key (e.g., 'calculator')
 *    - This key is used to find the service in container
 *    - Must match the key used in service provider
 * 
 * 3. SERVICE PROVIDER:
 *    - Registers the binding: 'calculator' => Calculator instance
 *    - Tells Laravel how to create the service
 * 
 * 4. SERVICE CONTAINER:
 *    - Stores all service bindings
 *    - Creates instances when requested
 *    - Like a factory for your services
 * 
 * 5. THE FLOW:
 *    Calculator::add() → __callStatic → getFacadeAccessor → 
 *    → Container lookup → Real instance → instance->add()
 */

// =====================================================
// TESTING THE CONCEPT
// =====================================================

// You can test this in tinker:
// php artisan tinker

// >>> Calculator::add(5, 3)
// => 8

// >>> Calculator::multiply(4, 7)
// => 28

// Behind the scenes:
// >>> app('calculator')
// => App\Services\Calculator {#3051}

// >>> app('calculator')->add(5, 3)
// => 8

// =====================================================
// COMMON MISTAKES AND SOLUTIONS
// =====================================================

/**
 * MISTAKE 1: Facade accessor doesn't match container binding
 * 
 * Facade: return 'calculator';
 * Provider: $this->app->bind('calc', ...);  // WRONG KEY!
 * 
 * SOLUTION: Make sure keys match exactly
 */

/**
 * MISTAKE 2: Forgetting to register service provider
 * 
 * SOLUTION: Add to config/app.php providers array
 */

/**
 * MISTAKE 3: Using instance methods as static
 * 
 * WRONG: Calculator::$result;  // Can't access properties
 * RIGHT: Use methods that return values
 */

/**
 * MISTAKE 4: Not understanding singleton vs bind
 * 
 * bind(): New instance each time
 * singleton(): Same instance always
 */
