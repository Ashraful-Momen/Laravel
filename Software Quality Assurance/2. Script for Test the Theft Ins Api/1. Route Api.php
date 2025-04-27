/*
    |--------------------------------------------------------------------------
    | Theft Insurance API Routes :  Version 2 : Ashraful
    |--------------------------------------------------------------------------
    |
    | These routes handle the Theft insurance quotation endpoints including
    | creating new quotations, listing existing ones, and viewing details,order...
    |
    */

    Route::prefix('theft-insurance-new/v2')->group(function () {
        // Store Quotation
        Route::post('/quotations-store', [TheftInsNewCusApiController::class, 'storeQuotation']);

        // List Quotations
        Route::get('/quotations-list', [TheftInsNewCusApiController::class, 'listQuotations'])
            ->middleware('auth:api');

        // Show Quotation Details
        Route::get('/quotations/{id}', [TheftInsNewCusApiController::class, 'showDetails'])
            ->middleware('auth:api');

        // Create order
        Route::post('/order-create', [TheftInsNewCusApiController::class, 'createOrder'])
            ->middleware('auth:api');

        // List orders
        Route::get('/order-list', [TheftInsNewCusApiController::class, 'orderList'])
            ->middleware('auth:api');

        // Order details
        Route::get('/order-details/{id}', [TheftInsNewCusApiController::class, 'orderDetails'])
            ->middleware('auth:api');

        // Policy Routes - Fix the URL conflict
        Route::get('/policy-detail/{id}', [TheftInsNewCusApiController::class, 'policy'])
            ->middleware('auth:api');

        Route::get('/policy-list', [TheftInsNewCusApiController::class, 'policyList'])
            ->middleware('auth:api');

        // Claim Routes
        Route::get('/claim/form/{policy_id}', [TheftInsNewCusApiController::class, 'claimForm'])
            ->middleware('auth:api');

        Route::post('/claim/submit', [TheftInsNewCusApiController::class, 'claimSubmit'])
            ->middleware('auth:api');

        Route::get('/claim/list', [TheftInsNewCusApiController::class, 'claimList'])
            ->middleware('auth:api');

        Route::get('/claim/details/{id}', [TheftInsNewCusApiController::class, 'claimDetails'])
            ->middleware('auth:api');
    });
