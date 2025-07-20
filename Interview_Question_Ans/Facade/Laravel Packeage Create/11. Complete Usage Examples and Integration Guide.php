<?php

// =====================================================
// COMPLETE USAGE EXAMPLES
// =====================================================

// 1. BASIC USAGE IN CONTROLLERS
// File: app/Http/Controllers/InvoiceController.php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Calculator; // or use YourVendor\Calculator\Facades\Calculator;

class InvoiceController extends Controller
{
    public function calculateTotals(Invoice $invoice)
    {
        // Calculate subtotal
        $subtotal = 0;
        foreach ($invoice->items as $item) {
            $lineTotal = Calculator::multiply($item->quantity, $item->price);
            $subtotal = Calculator::add($subtotal, $lineTotal);
        }
        
        // Calculate tax
        $taxAmount = Calculator::percentage($subtotal, $invoice->tax_rate);
        
        // Calculate discount
        $discountAmount = Calculator::percentage($subtotal, $invoice->discount_rate);
        
        // Calculate final total
        $total = Calculator::add($subtotal, $taxAmount);
        $total = Calculator::subtract($total, $discountAmount);
        
        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'calculation_history' => Calculator::getHistory()
        ];
    }
}

// =====================================================
// 2. USING IN BLADE TEMPLATES
// File: resources/views/invoice.blade.php

@extends('layouts.app')

@section('content')
<div class="invoice">
    <h2>Invoice Calculator</h2>
    
    <!-- Using custom Blade directive -->
    <p>Quick calculation: @calculate(add(100, 50))</p>
    <p>Tax amount: @calculator(percentage, 1000, 15)</p>
    
    <!-- Using in PHP blocks -->
    @php
        $subtotal = 1000;
        $tax = Calculator::percentage($subtotal, 15);
        $total = Calculator::add($subtotal, $tax);
    @endphp
    
    <table>
        <tr>
            <td>Subtotal:</td>
            <td>${{ $subtotal }}</td>
        </tr>
        <tr>
            <td>Tax (15%):</td>
            <td>${{ $tax }}</td>
        </tr>
        <tr>
            <td>Total:</td>
            <td>${{ $total }}</td>
        </tr>
    </table>
</div>
@endsection

// =====================================================
// 3. API ENDPOINT USAGE
// File: routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Single calculation endpoint
Route::post('/calculate', function (Request $request) {
    $validated = $request->validate([
        'operation' => 'required|string',
        'a' => 'required|numeric',
        'b' => 'required_unless:operation,sqrt|numeric'
    ]);
    
    try {
        $result = match($validated['operation']) {
            'add' => Calculator::add($validated['a'], $validated['b']),
            'subtract' => Calculator::subtract($validated['a'], $validated['b']),
            'multiply' => Calculator::multiply($validated['a'], $validated['b']),
            'divide' => Calculator::divide($validated['a'], $validated['b']),
            'sqrt' => Calculator::sqrt($validated['a']),
            default => throw new Exception('Invalid operation')
        };
        
        return response()->json([
            'success' => true,
            'result' => $result
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 400);
    }
});

// Batch calculations endpoint
Route::post('/calculate/batch', function (Request $request) {
    $calculations = $request->input('calculations', []);
    $results = [];
    
    foreach ($calculations as $calc) {
        try {
            $result = Calculator::{$calc['operation']}(...$calc['operands']);
            $results[] = [
                'operation' => $calc['operation'],
                'operands' => $calc['operands'],
                'result' => $result,
                'success' => true
            ];
        } catch (Exception $e) {
            $results[] = [
                'operation' => $calc['operation'],
                'operands' => $calc['operands'],
                'error' => $e->getMessage(),
                'success' => false
            ];
        }
    }
    
    return response()->json(['results' => $results]);
});

// =====================================================
// 4. USING WITH MODELS (Trait Example)
// File: app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use YourVendor\Calculator\Traits\HasCalculations;

class Product extends Model
{
    use HasCalculations;
    
    protected $fillable = ['name', 'price', 'cost', 'tax_rate'];
    
    // Calculate profit margin
    public function getProfitMarginAttribute()
    {
        $profit = $this->calculateTotal('price', 'cost', 'subtract');
        return Calculator::divide(
            Calculator::multiply($profit, 100),
            $this->price
        );
    }
    
    // Calculate price with tax
    public function getPriceWithTaxAttribute()
    {
        return $this->calculatePercentage('price', $this->tax_rate);
    }
}

// =====================================================
// 5. ARTISAN COMMAND USAGE
// In terminal:

// Simple calculation
php artisan calculator:calculate add 100 50
// Output: Result: 150

// With precision option
php artisan calculator:calculate divide 10 3 --precision=4
// Output: Result: 3.3333

// =====================================================
// 6. TESTING YOUR INTEGRATION
// File: tests/Feature/CalculatorIntegrationTest.php

namespace Tests\Feature;

use Tests\TestCase;
use Calculator;

class CalculatorIntegrationTest extends TestCase
{
    public function test_calculator_facade_works()
    {
        $result = Calculator::add(5, 3);
        $this->assertEquals(8, $result);
    }
    
    public function test_api_endpoint()
    {
        $response = $this->postJson('/api/calculate', [
            'operation' => 'multiply',
            'a' => 5,
            'b' => 4
        ]);
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'result' => 20
                 ]);
    }
    
    public function test_batch_calculations()
    {
        $response = $this->postJson('/api/calculate/batch', [
            'calculations' => [
                ['operation' => 'add', 'operands' => [10, 5]],
                ['operation' => 'multiply', 'operands' => [3, 7]],
                ['operation' => 'divide', 'operands' => [20, 4]]
            ]
        ]);
        
        $response->assertStatus(200);
        $results = $response->json('results');
        
        $this->assertEquals(15, $results[0]['result']);
        $this->assertEquals(21, $results[1]['result']);
        $this->assertEquals(5, $results[2]['result']);
    }
}

// =====================================================
// 7. SERVICE CONTAINER BINDING VARIATIONS
// File: app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use YourVendor\Calculator\CalculatorService;
use YourVendor\Calculator\Contracts\CalculatorInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind interface to implementation
        $this->app->bind(CalculatorInterface::class, CalculatorService::class);
        
        // Contextual binding
        $this->app->when(FinancialController::class)
                  ->needs(CalculatorInterface::class)
                  ->give(function () {
                      return new CalculatorService(4); // 4 decimal precision
                  });
        
        // Tagged binding for multiple calculators
        $this->app->bind('calculator.basic', function () {
            return new CalculatorService(2);
        });
        
        $this->app->bind('calculator.scientific', function () {
            return new CalculatorService(8);
        });
        
        $this->app->tag(['calculator.basic', 'calculator.scientific'], 'calculators');
    }
}

// =====================================================
// 8. DEPENDENCY INJECTION EXAMPLE
// File: app/Services/PricingService.php

namespace App\Services;

use YourVendor\Calculator\Contracts\CalculatorInterface;

class PricingService
{
    protected $calculator;
    
    public function __construct(CalculatorInterface $calculator)
    {
        $this->calculator = $calculator;
    }
    
    public function calculateFinalPrice($basePrice, $taxRate, $discountRate = 0)
    {
        // Add tax
        $taxAmount = $this->calculator->percentage($basePrice, $taxRate);
        $priceWithTax = $this->calculator->add($basePrice, $taxAmount);
        
        // Apply discount if any
        if ($discountRate > 0) {
            $discountAmount = $this->calculator->percentage($priceWithTax, $discountRate);
            return $this->calculator->subtract($priceWithTax, $discountAmount);
        }
        
        return $priceWithTax;
    }
}

// =====================================================
// 9. EVENT LISTENER EXAMPLE
// File: app/Listeners/CalculationLogger.php

namespace App\Listeners;

use YourVendor\Calculator\Events\CalculationPerformed;
use Illuminate\Support\Facades\Log;
use App\Models\CalculationLog;

class CalculationLogger
{
    public function handle(CalculationPerformed $event)
    {
        // Log to file
        Log::channel('calculations')->info('Calculation performed', [
            'operation' => $event->operation,
            'operands' => $event->operands,
            'result' => $event->result
        ]);
        
        // Save to database
        CalculationLog::create([
            'operation' => $event->operation,
            'operands' => json_encode($event->operands),
            'result' => $event->result,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip()
        ]);
    }
}

// =====================================================
// 10. VUE.JS INTEGRATION EXAMPLE
// File: resources/js/components/Calculator.vue

<template>
    <div class="calculator">
        <h3>Calculator</h3>
        <input v-model.number="a" type="number" placeholder="First number">
        <select v-model="operation">
            <option value="add">Add</option>
            <option value="subtract">Subtract</option>
            <option value="multiply">Multiply</option>
            <option value="divide">Divide</option>
        </select>
        <input v-model.number="b" type="number" placeholder="Second number">
        <button @click="calculate">Calculate</button>
        
        <div v-if="result !== null" class="result">
            Result: {{ result }}
        </div>
        
        <div v-if="error" class="error">
            Error: {{ error }}
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            a: null,
            b: null,
            operation: 'add',
            result: null,
            error: null
        }
    },
    
    methods: {
        async calculate() {
            this.error = null;
            this.result = null;
            
            try {
                const response = await axios.post('/api/calculate', {
                    operation: this.operation,
                    a: this.a,
                    b: this.b
                });
                
                this.result = response.data.result;
            } catch (error) {
                this.error = error.response?.data?.error || 'An error occurred';
            }
        }
    }
}
</script>
