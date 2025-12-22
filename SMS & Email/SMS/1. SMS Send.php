.env: 
---------

#****: Use this email config =>
# # Mail Configuration - FIXED: Changed from log to smtp for actual email delivery
MAIL_MAILER=smtp
MAIL_HOST=smtppro.zoho.com
MAIL_PORT=465
MAIL_USERNAME=stbl.erp@smartbd.com
MAIL_PASSWORD=vdm8*bfH
MAIL_FROM_ADDRESS=stbl.erp@smartbd.com
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ENCRYPTION=ssl
MAIL_DEFAULT_SENDER=stbl.erp@smartbd.com


#****: Use this sms config =>
# # SMS Configuration
SMS_URL='https://bulksmsbd.net/api/smsapi?api_key=KsNp0AcYqTNzTxCpoVA6&type=text&number={phone}&senderid=8809617611744&message={message}'
===================================================================

#route: web.php: 
------------------

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



//Test SMS
Route::get('/test-sms-service', function () {
    try {
        $smsService = app(\Webkul\Admin\Services\SmsService::class);

        // Test SMS sending
        $result = $smsService->sendSms(
            '01859385787',
            'Test message from SmsService - Integration successful!'
        );

        return "SMS Service Test: " . ($result ? 'Success' : 'Failed');
    } catch (\Exception $e) {
        return "Error testing SMS Service: " . $e->getMessage();
    }
});





===================================================================
service: /home/ashraful/UniSoft Ltd/Backend/saffron-backend-3/packages/Webkul/Admin/src/Services/SmsService.php
----------
<?php

namespace Webkul\Admin\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS using the configured SMS API.
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms($phoneNumber, $message)
    {
        try {
            $apiKey = env('SMS_API_KEY', 'KsNp0AcYqTNzTxCpoVA6');
            $senderId = env('SMS_SENDER_ID', '8809617611744');
            $url = env('SMS_URL', 'https://bulksmsbd.net/api/smsapi');

            $fullUrl = $url . '?api_key=' . $apiKey . '&type=text&number=' . $phoneNumber . '&senderid=' . $senderId . '&message=' . urlencode($message);

            $response = Http::get($fullUrl);

            if ($response->successful()) {
                $responseData = json_decode($response->body(), true);

                Log::info('SMS sent successfully', [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $responseData
                ];
            } else {
                Log::error('Failed to send SMS', [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'response' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('SMS sending error', [
                'phone_number' => $phoneNumber,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMS sending error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}


