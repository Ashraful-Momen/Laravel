#Route : 
----------
// Asrhaful : Theft Insurance

Route::get('/theft-insurance-form', [TheftInsNewCusController::class, 'showQuotationForm'])->name('theft-insurance.form');
Route::post('/theft-insurance/quotation', [TheftInsNewCusController::class, 'storeQuotation'])->name('theft-insurance.quotation');

Route::get('/theft-insurance/quotations', [TheftInsNewCusController::class, 'listQuotations'])->name('theft-insurance.quotations');
Route::get('/theft-insurance/quotations/{id}', [TheftInsNewCusController::class, 'showDetails'])->name('theft-insurance.details');


Route::post('/theft-insurance/order/create',[TheftInsNewCusController::class,'createOrder'])->name('theft-insurance.order.create');
Route::get('/theft-insurance/order/list',[TheftInsNewCusController::class,'orderList'])->name('theft-insurance.order.list');
Route::get('/theft-insurance/order/details/{id}',[TheftInsNewCusController::class,'orderDetails'])->name('theft-insurance.order.details');


Route::get('/theft-insurance/policy/{id}', [TheftInsNewCusController::class, 'policy'])->name('theft-insurance.policy');

// //policy list and details :
Route::get('/theft-insurance/policies/list', [TheftInsNewCusController::class, 'policyList'])->name('theft-insurance.policy.list');
// //not complete : ___________________
Route::get('/theft-insurance/policy/details/{id}', [TheftInsNewCusController::class, 'policy'])->name('theft-insurance.policy.show');

// //claim :
Route::get('/theft-insurance/claim/{id}', [TheftInsNewCusController::class, 'claimForm'])->name('theft-insurance.claim.form');
Route::post('/theft-insurance/claim/submit', [TheftInsNewCusController::class, 'claimSubmit'])->name('theft-insurance.claim.submit');



Route::get('/theft-insurance/claims/list', [TheftInsNewCusController::class, 'claimList'])->name('theft-insurance.claim.list');
Route::get('/theft-insurance/claim/details/{id}', [TheftInsNewCusController::class, 'claimDetails'])->name('theft-insurance.claim.detail');

//update the documents:
Route::post('/theft-insurance/update-documents/{id}', [TheftInsNewCusController::class, 'updateDocuments'])->name('theft-insurance.update-documents');
