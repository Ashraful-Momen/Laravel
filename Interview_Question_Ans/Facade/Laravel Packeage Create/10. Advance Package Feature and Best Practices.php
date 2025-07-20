<?php

// =====================================================
// ADVANCED FEATURES FOR YOUR CALCULATOR PACKAGE
// =====================================================

// 1. CONTRACTS/INTERFACES
// File: src/Contracts/CalculatorInterface.php

namespace YourVendor\Calculator\Contracts;

interface CalculatorInterface
{
    public function add($a, $b = null);
    public function subtract($a, $b = null);
    public function multiply($a, $b);
    public function divide($a, $b);
    public function getResult();
    public function reset();
}

// =====================================================
// 2. EVENTS
// File: src/Events/CalculationPerformed.php

namespace YourVendor\Calculator\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalculationPerformed
{
    use Dispatchable, SerializesModels;

    public $operation;
    public $operands;
    public $result;
    public $timestamp;

    public function __construct($operation, $operands, $result)
    {
        $this->operation = $operation;
        $this->operands = $operands;
        $this->result = $result;
        $this->timestamp = now();
    }
}

// =====================================================
// 3. LISTENERS
// File: src/Listeners/LogCalculation.php

namespace YourVendor\Calculator\Listeners;

use YourVendor\Calculator\Events\CalculationPerformed;
use Illuminate\Support\Facades\Log;

class LogCalculation
{
    public function handle(CalculationPerformed $event)
    {
        Log::info('Calculation performed', [
            'operation' => $event->operation,
            'operands' => $event->operands,
            'result' => $event->result,
            'timestamp' => $event->timestamp
        ]);
    }
}

// =====================================================
// 4. MIDDLEWARE FOR API ROUTES
// File: src/Http/Middleware/ValidateCalculation.php

namespace YourVendor\Calculator\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateCalculation
{
    public function handle(Request $request, Closure $next)
    {
        $rules = [
            'operation' => 'required|in:add,subtract,multiply,divide,power,sqrt,percentage',
            'a' => 'required|numeric',
            'b' => 'required_unless:operation,sqrt|numeric'
        ];

        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        return $next($request);
    }
}

// =====================================================
// 5. API CONTROLLER
// File: src/Http/Controllers/CalculatorApiController.php

namespace YourVendor\Calculator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use YourVendor\Calculator\Facades\Calculator;

class CalculatorApiController extends Controller
{
    public function calculate(Request $request)
    {
        $operation = $request->input('operation');
        $a = $request->input('a');
        $b = $request->input('b');

        try {
            $result = $this->performOperation($operation, $a, $b);
            
            return response()->json([
                'success' => true,
                'result' => $result,
                'operation' => $operation,
                'operands' => compact('a', 'b')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function history()
    {
        return response()->json([
            'success' => true,
            'history' => Calculator::getHistory(),
            'count' => count(Calculator::getHistory())
        ]);
    }

    public function batch(Request $request)
    {
        $calculations = $request->input('calculations', []);
        $results = [];

        foreach ($calculations as $calc) {
            try {
                $results[] = [
                    'operation' => $calc['operation'],
                    'result' => $this->performOperation(
                        $calc['operation'],
                        $calc['a'],
                        $calc['b'] ?? null
                    )
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'operation' => $calc['operation'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    private function performOperation($operation, $a, $b = null)
    {
        switch ($operation) {
            case 'add':
                return Calculator::add($a, $b);
            case 'subtract':
                return Calculator::subtract($a, $b);
            case 'multiply':
                return Calculator::multiply($a, $b);
            case 'divide':
                return Calculator::divide($a, $b);
            case 'power':
                return Calculator::power($a, $b);
            case 'sqrt':
                return Calculator::sqrt($a);
            case 'percentage':
                return Calculator::percentage($a, $b);
            default:
                throw new \InvalidArgumentException("Unknown operation: {$operation}");
        }
    }
}

// =====================================================
// 6. ROUTES
// File: routes/api.php

use Illuminate\Support\Facades\Route;
use YourVendor\Calculator\Http\Controllers\CalculatorApiController;
use YourVendor\Calculator\Http\Middleware\ValidateCalculation;

Route::prefix('api/calculator')->group(function () {
    Route::post('/calculate', [CalculatorApiController::class, 'calculate'])
        ->middleware(ValidateCalculation::class);
    
    Route::get('/history', [CalculatorApiController::class, 'history']);
    
    Route::post('/batch', [CalculatorApiController::class, 'batch']);
});

// =====================================================
// 7. BLADE DIRECTIVES
// File: src/CalculatorServiceProvider.php (updated boot method)

public function boot()
{
    // Previous boot code...
    
    // Register Blade directives
    Blade::directive('calculate', function ($expression) {
        return "<?php echo Calculator::$expression; ?>";
    });
    
    Blade::directive('calculator', function ($expression) {
        list($operation, $params) = explode(',', $expression, 2);
        return "<?php echo Calculator::{$operation}({$params}); ?>";
    });
}

// =====================================================
// 8. ARTISAN COMMANDS
// File: src/Console/Commands/CalculateCommand.php

namespace YourVendor\Calculator\Console\Commands;

use Illuminate\Console\Command;
use YourVendor\Calculator\Facades\Calculator;

class CalculateCommand extends Command
{
    protected $signature = 'calculator:calculate 
                            {operation : The operation to perform (add, subtract, multiply, divide)}
                            {a : First operand}
                            {b? : Second operand}
                            {--precision=2 : Number of decimal places}';

    protected $description = 'Perform a calculation using the calculator';

    public function handle()
    {
        $operation = $this->argument('operation');
        $a = (float) $this->argument('a');
        $b = $this->argument('b') ? (float) $this->argument('b') : null;
        $precision = (int) $this->option('precision');

        Calculator::setPrecision($precision);

        try {
            $result = $this->performCalculation($operation, $a, $b);
            
            $this->info("Result: {$result}");
            
            $this->table(
                ['Operation', 'A', 'B', 'Result'],
                [[$operation, $a, $b ?? 'N/A', $result]]
            );
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function performCalculation($operation, $a, $b)
    {
        switch ($operation) {
            case 'add':
                return Calculator::add($a, $b);
            case 'subtract':
                return Calculator::subtract($a, $b);
            case 'multiply':
                return Calculator::multiply($a, $b);
            case 'divide':
                return Calculator::divide($a, $b);
            default:
                throw new \InvalidArgumentException("Invalid operation: {$operation}");
        }
    }
}

// Register command in service provider
public function boot()
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            CalculateCommand::class,
        ]);
    }
}

// =====================================================
// 9. MACROS - Extend Calculator at runtime
// File: src/CalculatorServiceProvider.php (in boot method)

use YourVendor\Calculator\CalculatorService;

public function boot()
{
    // Allow macros on the calculator service
    CalculatorService::macro('factorial', function ($n) {
        if ($n < 0) {
            throw new \InvalidArgumentException("Factorial is not defined for negative numbers");
        }
        
        $result = 1;
        for ($i = 2; $i <= $n; $i++) {
            $result *= $i;
        }
        
        return $result;
    });
    
    CalculatorService::macro('fibonacci', function ($n) {
        if ($n < 0) {
            throw new \InvalidArgumentException("Fibonacci is not defined for negative numbers");
        }
        
        if ($n <= 1) return $n;
        
        $a = 0;
        $b = 1;
        
        for ($i = 2; $i <= $n; $i++) {
            $temp = $a + $b;
            $a = $b;
            $b = $temp;
        }
        
        return $b;
    });
}

// =====================================================
// 10. TRAIT FOR MODELS
// File: src/Traits/HasCalculations.php

namespace YourVendor\Calculator\Traits;

use YourVendor\Calculator\Facades\Calculator;

trait HasCalculations
{
    public function calculateTotal($field1, $field2, $operation = 'add')
    {
        $a = $this->getAttribute($field1);
        $b = $this->getAttribute($field2);
        
        switch ($operation) {
            case 'add':
                return Calculator::add($a, $b);
            case 'subtract':
                return Calculator::subtract($a, $b);
            case 'multiply':
                return Calculator::multiply($a, $b);
            case 'divide':
                return Calculator::divide($a, $b);
            default:
                throw new \InvalidArgumentException("Invalid operation: {$operation}");
        }
    }
    
    public function calculatePercentage($field, $percentage)
    {
        $value = $this->getAttribute($field);
        return Calculator::percentage($value, $percentage);
    }
}
