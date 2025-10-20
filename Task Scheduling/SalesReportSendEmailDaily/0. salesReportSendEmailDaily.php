<?php

// ========================== ROUTES (web.php or api.php) ==========================
Route::get('/send-daily-email', [DailyEmailController::class, 'sendDailyEmail'])->name('send.daily.email');

// ========================== COMMAND (for Scheduler) ==========================
// php artisan make:command SendDailyEmailCommand

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DailyEmailController;

class SendDailyEmailCommand extends Command
{
    protected $signature = 'email:send-daily';
    protected $description = 'Send daily email report at 00:00';

    public function handle()
    {
        $controller = new DailyEmailController();
        $result = $controller->sendDailyEmail();
        
        if ($result) {
            $this->info('Daily email sent successfully!');
        } else {
            $this->error('Failed to send daily email!');
        }
    }
}

// ========================== SCHEDULER (app/Console/Kernel.php) ==========================
protected function schedule(Schedule $schedule)
{
    // Send email every night at 00:00
    $schedule->command('email:send-daily')
             ->dailyAt('00:00')
             ->timezone('Asia/Dhaka'); // Adjust timezone as needed
}

// ========================== CONTROLLER ==========================
namespace App\Http\Controllers;

use Illuminate\Http\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DailyEmailController extends Controller
{
    public function sendDailyEmail()
    {
        try {
            $today = Carbon::today()->toDateString();
            
            Log::info('Starting daily email generation', ['date' => $today]);
            
            // Get simple data for each category
            $emailData = $this->getDailyEmailData($today);
            
            // Send email
            $recipients = [
                'admin@company.com',
                'manager@company.com',
                // Add more recipients as needed
            ];
            
            foreach ($recipients as $recipient) {
                Mail::send('emails.daily-report', $emailData, function ($message) use ($recipient, $today) {
                    $message->to($recipient)
                           ->subject('Daily Insurance Report - ' . $today);
                });
            }
            
            Log::info('Daily email sent successfully', [
                'date' => $today,
                'recipients_count' => count($recipients),
                'total_records' => $emailData['total_count']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Daily email sent successfully',
                'data' => $emailData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send daily email', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function getDailyEmailData($date)
    {
        $data = [
            'date' => $date,
            'categories' => [],
            'total_count' => 0
        ];
        
        // Category 8: Life And Health
        $lifeHealth = $this->getLifeHealthData($date);
        if (!empty($lifeHealth)) {
            $data['categories'][] = $lifeHealth;
            $data['total_count'] += $lifeHealth['total_count'];
        }
        
        // Category 12: HDC Card
        $hdcCard = $this->getHdcCardData($date);
        if (!empty($hdcCard)) {
            $data['categories'][] = $hdcCard;
            $data['total_count'] += $hdcCard['total_count'];
        }
        
        // Category 16: Tele Medicine
        $teleMedicine = $this->getTeleMedicineData($date);
        if (!empty($teleMedicine)) {
            $data['categories'][] = $teleMedicine;
            $data['total_count'] += $teleMedicine['total_count'];
        }
        
        // Category 10: Motor Insurance
        $motorInsurance = $this->getMotorInsuranceData($date);
        if (!empty($motorInsurance)) {
            $data['categories'][] = $motorInsurance;
            $data['total_count'] += $motorInsurance['total_count'];
        }
        
        // Category 11: Cattle Insurance
        $cattleInsurance = $this->getCattleInsuranceData($date);
        if (!empty($cattleInsurance)) {
            $data['categories'][] = $cattleInsurance;
            $data['total_count'] += $cattleInsurance['total_count'];
        }
        
        // Category 5: Travel Insurance
        $travelInsurance = $this->getTravelInsuranceData($date);
        if (!empty($travelInsurance)) {
            $data['categories'][] = $travelInsurance;
            $data['total_count'] += $travelInsurance['total_count'];
        }
        
        // Category 6: Device Insurance
        $deviceInsurance = $this->getDeviceInsuranceData($date);
        if (!empty($deviceInsurance)) {
            $data['categories'][] = $deviceInsurance;
            $data['total_count'] += $deviceInsurance['total_count'];
        }
        
        // Category 20: Fire Insurance
        $fireInsurance = $this->getFireInsuranceData($date);
        if (!empty($fireInsurance)) {
            $data['categories'][] = $fireInsurance;
            $data['total_count'] += $fireInsurance['total_count'];
        }
        
        // Category 21: Theft Insurance
        $theftInsurance = $this->getTheftInsuranceData($date);
        if (!empty($theftInsurance)) {
            $data['categories'][] = $theftInsurance;
            $data['total_count'] += $theftInsurance['total_count'];
        }
        
        return $data;
    }
    
    // Simple query for Life And Health (Category 8)
    private function getLifeHealthData($date)
    {
        $results = DB::table('order_parents')
            ->select(
                DB::raw("8 as cat_id"),
                DB::raw("'Life & Health' as category_name"),
                'hospital_card_id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 8)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for HDC Card (Category 12)
    private function getHdcCardData($date)
    {
        $results = DB::table('order_parents')
            ->select(
                DB::raw("12 as cat_id"),
                DB::raw("'HDC Card' as category_name"),
                'hospital_card_id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 12)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for Tele Medicine (Category 16)
    private function getTeleMedicineData($date)
    {
        $results = DB::table('tele_medicine_orders')
            ->select(
                DB::raw("16 as cat_id"),
                DB::raw("'Tele Medicine' as category_name"),
                'product_id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 16)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for Motor Insurance (Category 10)
    private function getMotorInsuranceData($date)
    {
        $results = DB::table('motor_order_parents')
            ->select(
                DB::raw("10 as cat_id"),
                DB::raw("'Motor Insurance' as category_name"),
                'ref_id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 10)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for Cattle Insurance (Category 11)
    private function getCattleInsuranceData($date)
    {
        $results = DB::table('cattle_order_list')
            ->select(
                DB::raw("11 as cat_id"),
                DB::raw("'Cattle Insurance' as category_name"),
                'order_ref_id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 11)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for Travel Insurance (Category 5)
    private function getTravelInsuranceData($date)
    {
        $results = DB::table('travel_ins_orders')
            ->select(
                DB::raw("5 as cat_id"),
                DB::raw("'Travel Insurance' as category_name"),
                'travel_ins_plans_category_id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 5)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for Device Insurance (Category 6)
    private function getDeviceInsuranceData($date)
    {
        $results = DB::table('device_insurances')
            ->select(
                DB::raw("6 as cat_id"),
                DB::raw("'Device Insurance' as category_name"),
                'id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for Fire Insurance (Category 20)
    private function getFireInsuranceData($date)
    {
        $results = DB::table('fire_insurance_new_orders')
            ->select(
                DB::raw("20 as cat_id"),
                DB::raw("'Fire Insurance' as category_name"),
                'id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 20)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
    
    // Simple query for Theft Insurance (Category 21)
    private function getTheftInsuranceData($date)
    {
        $results = DB::table('theft_insurance_new_orders')
            ->select(
                DB::raw("21 as cat_id"),
                DB::raw("'Theft Insurance' as category_name"),
                'id as pkg_id',
                DB::raw("'Instasure' as reseller"),
                DB::raw("COUNT(*) as total_count")
            )
            ->where('category_id', 21)
            ->whereDate('created_at', $date)
            ->first();
            
        return $results && $results->total_count > 0 ? [
            'cat_id' => $results->cat_id,
            'category_name' => $results->category_name,
            'pkg_id' => $results->pkg_id ?? 'N/A',
            'reseller' => $results->reseller,
            'total_count' => $results->total_count
        ] : null;
    }
}

// ========================== EMAIL VIEW (resources/views/emails/daily-report.blade.php) ==========================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Insurance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .summary {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-row {
            background-color: #d4edda !important;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding: 15px;
            background: #6c757d;
            color: white;
            border-radius: 5px;
            text-align: center;
            font-size: 12px;
        }
        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Daily Insurance Report</h1>
            <p>Date: {{ $date }}</p>
        </div>

        <div class="summary">
            <h3>Summary</h3>
            <p><strong>Total Records:</strong> {{ $total_count }}</p>
            <p><strong>Categories Processed:</strong> {{ count($categories) }}</p>
            <p><strong>Generated At:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>

        @if(count($categories) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>
                        <th>Package ID</th>
                        <th>Reseller</th>
                        <th>Total Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category['cat_id'] }}</td>
                            <td>{{ $category['category_name'] }}</td>
                            <td>{{ $category['pkg_id'] }}</td>
                            <td>{{ $category['reseller'] }}</td>
                            <td>{{ number_format($category['total_count']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4"><strong>TOTAL</strong></td>
                        <td><strong>{{ number_format($total_count) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="no-data">
                <p>No insurance records found for {{ $date }}</p>
            </div>
        @endif

        <div class="footer">
            <p>This is an automated email from the Insurance Management System</p>
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

<?php
// ========================== TESTING ROUTE (Optional) ==========================
// Add this to test the email manually
Route::get('/test-daily-email', function() {
    $controller = new App\Http\Controllers\DailyEmailController();
    return $controller->sendDailyEmail();
});
?>
