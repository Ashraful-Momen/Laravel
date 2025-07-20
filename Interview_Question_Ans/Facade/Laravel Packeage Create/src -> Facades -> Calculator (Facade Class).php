<?php

namespace YourVendor\Calculator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Calculator Facade
 * 
 * @method static float add($a, $b = null)
 * @method static float subtract($a, $b = null)
 * @method static float multiply($a, $b)
 * @method static float divide($a, $b)
 * @method static float power($base, $exponent)
 * @method static float sqrt($number)
 * @method static float percentage($number, $percentage)
 * @method static int modulo($a, $b)
 * @method static float getResult()
 * @method static \YourVendor\Calculator\CalculatorService reset()
 * @method static \YourVendor\Calculator\CalculatorService clearHistory()
 * @method static array getHistory()
 * @method static \YourVendor\Calculator\CalculatorService setPrecision($precision)
 * 
 * @see \YourVendor\Calculator\CalculatorService
 */
class Calculator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'calculator';
    }
}
