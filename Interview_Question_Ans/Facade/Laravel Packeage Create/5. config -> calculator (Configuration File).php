<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Calculator Precision
    |--------------------------------------------------------------------------
    |
    | This value determines the number of decimal places to use for
    | calculations. You can adjust this based on your needs.
    |
    */
    'precision' => env('CALCULATOR_PRECISION', 2),

    /*
    |--------------------------------------------------------------------------
    | Enable History
    |--------------------------------------------------------------------------
    |
    | When enabled, the calculator will keep track of all operations
    | performed during the application lifecycle.
    |
    */
    'enable_history' => env('CALCULATOR_ENABLE_HISTORY', true),

    /*
    |--------------------------------------------------------------------------
    | History Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of operations to keep in history.
    | Set to null for unlimited history.
    |
    */
    'history_limit' => env('CALCULATOR_HISTORY_LIMIT', 100),
];
