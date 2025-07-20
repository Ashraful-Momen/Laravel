<?php

namespace YourVendor\Calculator;

/**
 * Calculator Service
 * 
 * This is the main service class that contains all calculator functionality
 */
class CalculatorService
{
    /**
     * The current result for chaining operations
     *
     * @var float
     */
    protected $result = 0;

    /**
     * History of operations
     *
     * @var array
     */
    protected $history = [];

    /**
     * Precision for floating point operations
     *
     * @var int
     */
    protected $precision;

    /**
     * Create a new Calculator instance
     *
     * @param int $precision
     */
    public function __construct($precision = 2)
    {
        $this->precision = $precision;
    }

    /**
     * Add two numbers or add to result
     *
     * @param float $a
     * @param float|null $b
     * @return float|$this
     */
    public function add($a, $b = null)
    {
        if ($b === null) {
            $this->result += $a;
            $this->addToHistory('add', $a, null, $this->result);
            return $this;
        }

        $result = round($a + $b, $this->precision);
        $this->addToHistory('add', $a, $b, $result);
        return $result;
    }

    /**
     * Subtract two numbers or subtract from result
     *
     * @param float $a
     * @param float|null $b
     * @return float|$this
     */
    public function subtract($a, $b = null)
    {
        if ($b === null) {
            $this->result -= $a;
            $this->addToHistory('subtract', $a, null, $this->result);
            return $this;
        }

        $result = round($a - $b, $this->precision);
        $this->addToHistory('subtract', $a, $b, $result);
        return $result;
    }

    /**
     * Multiply two numbers
     *
     * @param float $a
     * @param float $b
     * @return float
     */
    public function multiply($a, $b)
    {
        $result = round($a * $b, $this->precision);
        $this->addToHistory('multiply', $a, $b, $result);
        return $result;
    }

    /**
     * Divide two numbers
     *
     * @param float $a
     * @param float $b
     * @return float
     * @throws \InvalidArgumentException
     */
    public function divide($a, $b)
    {
        if ($b == 0) {
            throw new \InvalidArgumentException("Division by zero is not allowed");
        }

        $result = round($a / $b, $this->precision);
        $this->addToHistory('divide', $a, $b, $result);
        return $result;
    }

    /**
     * Calculate power
     *
     * @param float $base
     * @param float $exponent
     * @return float
     */
    public function power($base, $exponent)
    {
        $result = round(pow($base, $exponent), $this->precision);
        $this->addToHistory('power', $base, $exponent, $result);
        return $result;
    }

    /**
     * Calculate square root
     *
     * @param float $number
     * @return float
     * @throws \InvalidArgumentException
     */
    public function sqrt($number)
    {
        if ($number < 0) {
            throw new \InvalidArgumentException("Cannot calculate square root of negative number");
        }

        $result = round(sqrt($number), $this->precision);
        $this->addToHistory('sqrt', $number, null, $result);
        return $result;
    }

    /**
     * Calculate percentage
     *
     * @param float $number
     * @param float $percentage
     * @return float
     */
    public function percentage($number, $percentage)
    {
        $result = round(($number * $percentage) / 100, $this->precision);
        $this->addToHistory('percentage', $number, $percentage, $result);
        return $result;
    }

    /**
     * Calculate modulo
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public function modulo($a, $b)
    {
        if ($b == 0) {
            throw new \InvalidArgumentException("Modulo by zero is not allowed");
        }

        $result = $a % $b;
        $this->addToHistory('modulo', $a, $b, $result);
        return $result;
    }

    /**
     * Get the current result
     *
     * @return float
     */
    public function getResult()
    {
        return round($this->result, $this->precision);
    }

    /**
     * Reset the calculator
     *
     * @return $this
     */
    public function reset()
    {
        $this->result = 0;
        return $this;
    }

    /**
     * Clear history
     *
     * @return $this
     */
    public function clearHistory()
    {
        $this->history = [];
        return $this;
    }

    /**
     * Get calculation history
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Set precision for calculations
     *
     * @param int $precision
     * @return $this
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
        return $this;
    }

    /**
     * Add operation to history
     *
     * @param string $operation
     * @param mixed $a
     * @param mixed $b
     * @param mixed $result
     */
    protected function addToHistory($operation, $a, $b, $result)
    {
        $this->history[] = [
            'operation' => $operation,
            'a' => $a,
            'b' => $b,
            'result' => $result,
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
