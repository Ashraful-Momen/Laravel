<?php

namespace YourVendor\Calculator\Tests;

use YourVendor\Calculator\CalculatorService;
use YourVendor\Calculator\Facades\Calculator;

class CalculatorTest extends TestCase
{
    /** @test */
    public function it_can_add_two_numbers()
    {
        $result = Calculator::add(5, 3);
        $this->assertEquals(8, $result);
    }

    /** @test */
    public function it_can_subtract_two_numbers()
    {
        $result = Calculator::subtract(10, 4);
        $this->assertEquals(6, $result);
    }

    /** @test */
    public function it_can_multiply_two_numbers()
    {
        $result = Calculator::multiply(4, 7);
        $this->assertEquals(28, $result);
    }

    /** @test */
    public function it_can_divide_two_numbers()
    {
        $result = Calculator::divide(20, 4);
        $this->assertEquals(5, $result);
    }

    /** @test */
    public function it_throws_exception_for_division_by_zero()
    {
        $this->expectException(\InvalidArgumentException::class);
        Calculator::divide(10, 0);
    }

    /** @test */
    public function it_can_chain_operations()
    {
        $result = Calculator::reset()
            ->add(10)
            ->add(5)
            ->subtract(3)
            ->getResult();
        
        $this->assertEquals(12, $result);
    }

    /** @test */
    public function it_can_calculate_power()
    {
        $result = Calculator::power(2, 3);
        $this->assertEquals(8, $result);
    }

    /** @test */
    public function it_can_calculate_square_root()
    {
        $result = Calculator::sqrt(16);
        $this->assertEquals(4, $result);
    }

    /** @test */
    public function it_can_calculate_percentage()
    {
        $result = Calculator::percentage(200, 15);
        $this->assertEquals(30, $result);
    }

    /** @test */
    public function it_maintains_history()
    {
        Calculator::clearHistory();
        Calculator::add(5, 3);
        Calculator::multiply(4, 2);
        
        $history = Calculator::getHistory();
        
        $this->assertCount(2, $history);
        $this->assertEquals('add', $history[0]['operation']);
        $this->assertEquals('multiply', $history[1]['operation']);
    }

    /** @test */
    public function it_respects_precision_setting()
    {
        Calculator::setPrecision(3);
        $result = Calculator::divide(10, 3);
        
        $this->assertEquals(3.333, $result);
    }

    /** @test */
    public function it_can_be_used_without_facade()
    {
        $calculator = new CalculatorService();
        $result = $calculator->add(5, 5);
        
        $this->assertEquals(10, $result);
    }
}
