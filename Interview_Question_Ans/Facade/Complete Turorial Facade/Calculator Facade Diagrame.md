=====================================================
COMPLETE LARAVEL FACADE FLOW - CALCULATOR EXAMPLE
=====================================================

1. FILE STRUCTURE
-----------------
app/
├── Services/
│   └── Calculator.php          (The real implementation)
├── Facades/
│   └── Calculator.php          (The facade - just a pointer)
└── Providers/
    └── CalculatorServiceProvider.php  (Registers in container)

config/
└── app.php                     (Register provider & alias)


2. THE COMPLETE FLOW WHEN YOU CALL: Calculator::add(5, 3)
----------------------------------------------------------

┌─────────────────────────────────────────────────────────────────┐
│                        YOUR CODE                                │
│                   Calculator::add(5, 3)                         │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    PHP INTERPRETER                              │
│  "Looking for static method 'add' in Calculator facade class"  │
│  Result: NOT FOUND! ❌                                         │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                  __callStatic() TRIGGERED                       │
│  (Inherited from Illuminate\Support\Facades\Facade)            │
│                                                                 │
│  public static function __callStatic($method, $args)           │
│  {                                                              │
│      // $method = 'add'                                         │
│      // $args = [5, 3]                                          │
│      $instance = static::getFacadeRoot();                       │
│      return $instance->$method(...$args);                       │
│  }                                                              │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    getFacadeRoot() CALLED                       │
│                                                                 │
│  public static function getFacadeRoot()                         │
│  {                                                              │
│      $name = static::getFacadeAccessor();  ───┐                │
│      return app($name);                       │                │
│  }                                            │                │
└───────────────────────────────────────────────┼─────────────────┘
                                                │
                                                ▼
┌─────────────────────────────────────────────────────────────────┐
│              YOUR getFacadeAccessor() CALLED                    │
│                  (In your Calculator facade)                    │
│                                                                 │
│  protected static function getFacadeAccessor()                  │
│  {                                                              │
│      return 'calculator';  // Returns the container key        │
│  }                                                              │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            │ 'calculator'
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                  app('calculator') CALLED                       │
│              (Laravel's Service Container)                      │
│                                                                 │
│  Container bindings:                                            │
│  ┌─────────────────────────────────────┐                      │
│  │ 'calculator' => function() {         │                      │
│  │     return new Calculator();         │ ← From ServiceProvider│
│  │ }                                    │                      │
│  └─────────────────────────────────────┘                      │
│                                                                 │
│  Returns: new App\Services\Calculator instance                 │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                 BACK TO __callStatic()                          │
│                                                                 │
│  $instance = [Calculator Service Object]                        │
│  return $instance->add(5, 3);  // Calls real method!          │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│              REAL Calculator::add() EXECUTES                    │
│                                                                 │
│  public function add($a, $b)                                    │
│  {                                                              │
│      return $a + $b;  // 5 + 3 = 8                            │
│  }                                                              │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      RESULT: 8                                  │
│          Returned all the way back to your code                │
└─────────────────────────────────────────────────────────────────┘


3. SERVICE CONTAINER REGISTRATION FLOW
--------------------------------------

CalculatorServiceProvider::register()
            │
            ▼
    $this->app->bind('calculator', function() {
        return new Calculator();
    })
            │
            ▼
    ┌──────────────────────────┐
    │   SERVICE CONTAINER       │
    │                          │
    │  Bindings Array:         │
    │  [                       │
    │    'calculator' => fn()  │
    │    'db' => fn()         │
    │    'cache' => fn()      │
    │    ...                   │
    │  ]                       │
    └──────────────────────────┘


4. KEY POINTS TO REMEMBER
-------------------------

a) The Facade class (App\Facades\Calculator):
   - Has NO real methods (no add, subtract, etc.)
   - Only has getFacadeAccessor() returning 'calculator'
   - Inherits __callStatic from base Facade

b) getFacadeAccessor():
   - Returns a STRING key ('calculator')
   - This key must match what's in ServiceProvider
   - Laravel uses this key to find the service

c) Service Provider:
   - Binds 'calculator' => Calculator instance
   - Registered in config/app.php
   - Runs during Laravel bootstrap

d) The Magic:
   - Static call → __callStatic → getFacadeAccessor → 
   - → Container lookup → Real instance → Method call

e) Why use Facades?
   - Clean syntax: Calculator::add() vs app('calculator')->add()
   - Easy testing: Can mock facades
   - Consistent API across Laravel


5. COMMON PATTERNS
------------------

// Facade with dependency injection
class CalculatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('calculator', function ($app) {
            return new Calculator(
                $app->make('config'),
                $app->make('logger')
            );
        });
    }
}

// Multiple facades for same service
class Math extends Facade {
    protected static function getFacadeAccessor() {
        return 'calculator';  // Same as Calculator facade!
    }
}

// Now both work:
Calculator::add(5, 3);
Math::add(5, 3);


6. DEBUGGING TIPS
-----------------

// See what's actually happening:
dd(app('calculator'));  // See the actual instance

// Check if binding exists:
dd(app()->bound('calculator'));  // true/false

// Get all bindings:
dd(array_keys(app()->getBindings()));
