#

public function aamrPaySuccess(Request $request)
{

            // -----------------------------------------------------
            $request->merge([
                'pgw_name' => "aamarPay",
                'pgw_status' => 'Complete',
                'pgw_shuffle_id' => $request->order_random_id,
                'pgw_response' => $request,
                'order_amount' => $request->order_amount,

            ]);
            // -----------------------------------------------------

            $call_back = "App\Http\Controllers\Cattle\ListController::order_payment"; //this function must be an static function either can't pass request value. 
            return $call_back($request);
}
