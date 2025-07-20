<?php

namespace YourVendor\Calculator;

use Illuminate\Support\ServiceProvider;

class CalculatorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/calculator.php', 'calculator'
        );

        // Register the main class to use with the facade
        $this->app->singleton('calculator', function ($app) {
            $precision = config('calculator.precision', 2);
            return new CalculatorService($precision);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/calculator.php' => config_path('calculator.php'),
            ], 'calculator-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['calculator'];
    }
}
