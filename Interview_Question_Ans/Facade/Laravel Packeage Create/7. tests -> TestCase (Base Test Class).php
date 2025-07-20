<?php

namespace YourVendor\Calculator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use YourVendor\Calculator\CalculatorServiceProvider;
use YourVendor\Calculator\Facades\Calculator;

abstract class TestCase extends Orchestra
{
    /**
     * Load package service provider
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            CalculatorServiceProvider::class,
        ];
    }

    /**
     * Load package alias
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'Calculator' => Calculator::class,
        ];
    }

    /**
     * Define environment setup
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
