# Dependency Inversion Principle (DIP) - Complete Guide with Laravel Examples

## Definition

> **"High-level modules should not depend on low-level modules. Both should depend on abstractions."**
> 
> **"Abstractions should not depend on details. Details should depend on abstractions."**

**In Simple Terms**: Don't depend on concrete classes, depend on interfaces. Let someone else decide what implementation to use.

## 1. Algorithm with ASCII Visualization

### Core DIP Algorithm:
```
1. Identify dependencies between high-level and low-level modules
2. Create abstractions (interfaces) for low-level modules
3. Make high-level modules depend on abstractions
4. Make low-level modules implement abstractions
5. Inject dependencies from outside (Dependency Injection)
```

### ASCII Visualization - DIP Violation (BAD):

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       DEPENDENCY INVERSION VIOLATION                    │
│                                                                         │
│  HIGH-LEVEL MODULE (Business Logic)                                     │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                OrderService                                     │   │
│  │                                                                 │   │
│  │  class OrderService {                                           │   │
│  │    private $emailService;                                       │   │
│  │    private $smsService;                                         │   │
│  │    private $mysqlDatabase;                                      │   │
│  │                                                                 │   │
│  │    public function __construct() {                              │   │
│  │      $this->emailService = new EmailService();    ←────┐       │   │
│  │      $this->smsService = new SmsService();        ←──┐ │       │   │
│  │      $this->mysqlDatabase = new MySqlDB();       ←─┐ │ │       │   │
│  │    }                                               │ │ │       │   │
│  │  }                                                 │ │ │       │   │
│  └─────────────────────────────────────────────────────┼─┼─┼───────┘   │
│                                                       │ │ │           │
│  LOW-LEVEL MODULES (Implementation Details)            │ │ │           │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────┼─┼─┼─────────┐ │
│  │   EmailService  │  │   SmsService    │  │     MySqlDatabase     │ │
│  │                 │  │                 │  │                       │ │
│  │ + sendEmail()   │  │ + sendSms()     │  │ + connect()           │ │
│  │ + configure()   │  │ + authenticate()│  │ + query()             │ │
│  └─────────────────┘  └─────────────────┘  └───────────────────────┘ │
│           ▲                      ▲                      ▲             │
│           │                      │                      │             │
│           └──────────────────────┼──────────────────────┘             │
│                                  │                                    │
│                           TIGHT COUPLING                              │
│                                                                       │
│  PROBLEMS:                                                            │
│  ❌ Hard to test (can't mock dependencies)                           │
│  ❌ Hard to change implementations                                    │
│  ❌ Violates Open/Closed Principle                                   │
│  ❌ High-level logic depends on low-level details                    │
└─────────────────────────────────────────────────────────────────────────┘
```

### ASCII Visualization - DIP Compliant (GOOD):

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       DEPENDENCY INVERSION COMPLIANT                    │
│                                                                         │
│  HIGH-LEVEL MODULE (Business Logic)                                     │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                OrderService                                     │   │
│  │                                                                 │   │
│  │  class OrderService {                                           │   │
│  │    private $notifier;                                           │   │
│  │    private $database;                                           │   │
│  │                                                                 │   │
│  │    public function __construct(                                 │   │
│  │      NotifierInterface $notifier,       ←────────────┐         │   │
│  │      DatabaseInterface $database        ←──────────┐ │         │   │
│  │    ) {                                             │ │         │   │
│  │      $this->notifier = $notifier;                  │ │         │   │
│  │      $this->database = $database;                  │ │         │   │
│  │    }                                               │ │         │   │
│  │  }                                                 │ │         │   │
│  └─────────────────────────────────────────────────────┼─┼─────────┘   │
│                           │                            │ │             │
│                           │ depends on                 │ │             │
│                           ▼                            │ │             │
│  ABSTRACTIONS (Interfaces)                             │ │             │
│  ┌─────────────────────────────────────────────────────┼─┼─────────┐   │
│  │  interface NotifierInterface                        │ │         │   │
│  │    + notify($message)                               │ │         │   │
│  │                                                     │ │         │   │
│  │  interface DatabaseInterface                        │ │         │   │
│  │    + save($data)                                    │ │         │   │
│  │    + find($id)                                      │ │         │   │
│  └─────────────────────────────────────────────────────┼─┼─────────┘   │
│                           ▲                            │ │             │
│                           │ implements                 │ │             │
│                           │                            │ │             │
│  LOW-LEVEL MODULES (Implementation Details)            │ │             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────┼─┼─────────┐   │
│  │ EmailNotifier   │  │  SmsNotifier    │  │   MySqlDatabase     │   │
│  │ implements      │  │ implements      │  │ implements          │   │
│  │ NotifierInterface│  │ NotifierInterface│  │ DatabaseInterface   │   │
│  │                 │  │                 │  │                     │   │
│  │ + notify()      │  │ + notify()      │  │ + save()            │   │
│  └─────────────────┘  └─────────────────┘  │ + find()            │   │
│                                            └─────────────────────┘   │
│                                                                       │
│  DEPENDENCY INJECTION CONTAINER                                       │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │  $orderService = new OrderService(                              │ │
│  │    new EmailNotifier(),    // Injectable!                       │ │
│  │    new MySqlDatabase()     // Configurable!                     │ │
│  │  );                                                             │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│                                                                       │
│  BENEFITS:                                                            │
│  ✅ Easy to test (inject mocks)                                      │
│  ✅ Easy to change implementations                                   │
│  ✅ Follows Open/Closed Principle                                   │
│  ✅ High-level logic independent of low-level details               │
└─────────────────────────────────────────────────────────────────────────┘
```

### DIP Flow Diagram:

```
DEPENDENCY FLOW WITH DIP:

WITHOUT DIP (BAD):
┌─────────────┐    directly    ┌─────────────┐
│ High-Level  │─────depends───▶│ Low-Level   │
│ Module      │      on        │ Module      │
│ (Business)  │                │ (Details)   │
└─────────────┘                └─────────────┘
     PROBLEM: Changes in low-level break high-level

WITH DIP (GOOD):
┌─────────────┐    depends    ┌─────────────┐    implements    ┌─────────────┐
│ High-Level  │─────on───────▶│ Abstraction │◀────────────────│ Low-Level   │
│ Module      │               │ (Interface) │                 │ Module      │
│ (Business)  │               │             │                 │ (Details)   │
└─────────────┘               └─────────────┘                 └─────────────┘
     BENEFIT: Both depend on stable abstraction

DEPENDENCY INJECTION FLOW:
┌─────────────┐
│  Container  │ (decides which implementation)
│     or      │
│ Main Entry  │
└──────┬──────┘
       │ creates & injects
       ▼
┌─────────────┐    uses    ┌─────────────┐
│ High-Level  │───────────▶│ Abstraction │
│ Module      │ (receives) │ (Interface) │
└─────────────┘            └─────────────┘
                                  ▲
                                  │ implemented by
                                  │
                           ┌─────────────┐
                           │ Low-Level   │
                           │ Module      │ (injected)
                           └─────────────┘
```

## 2. Easy PHP Code Examples

### Simple Example - Report Generator

```php
<?php
// ❌ BAD: DIP Violation
class BadReportGenerator {
    private $emailService;
    private $fileStorage;
    
    public function __construct() {
        // Hard dependencies - violates DIP!
        $this->emailService = new EmailService();
        $this->fileStorage = new FileStorage();
    }
    
    public function generateAndSend($data) {
        $report = $this->generateReport($data);
        
        // Tightly coupled to specific implementations
        $this->fileStorage->save('report.pdf', $report);
        $this->emailService->send('admin@example.com', 'Report Ready', $report);
        
        return 'Report sent via email and saved to file';
    }
    
    private function generateReport($data) {
        return "Report: " . json_encode($data);
    }
}

class EmailService {
    public function send($to, $subject, $body) {
        // Email implementation
        mail($to, $subject, $body);
    }
}

class FileStorage {
    public function save($filename, $content) {
        file_put_contents($filename, $content);
    }
}

// Problems with this approach:
// - Can't test without sending real emails
// - Can't change to SMS or Slack notifications
// - Hard to switch from file to database storage

// ✅ GOOD: DIP Compliant
interface NotificationInterface {
    public function send($recipient, $message);
}

interface StorageInterface {
    public function save($identifier, $content);
}

// High-level module depends on abstractions
class GoodReportGenerator {
    private $notifier;
    private $storage;
    
    // Dependencies injected, not created internally
    public function __construct(NotificationInterface $notifier, StorageInterface $storage) {
        $this->notifier = $notifier;
        $this->storage = $storage;
    }
    
    public function generateAndSend($data) {
        $report = $this->generateReport($data);
        
        // Uses abstractions, not concrete implementations
        $reportId = $this->storage->save('report_' . time(), $report);
        $this->notifier->send('admin@example.com', "Report ready: {$reportId}");
        
        return "Report generated and notification sent";
    }
    
    private function generateReport($data) {
        return "Report: " . json_encode($data);
    }
}

// Low-level modules implement abstractions
class EmailNotification implements NotificationInterface {
    public function send($recipient, $message) {
        mail($recipient, 'Report Notification', $message);
        echo "Email sent to: {$recipient}\n";
    }
}

class SmsNotification implements NotificationInterface {
    public function send($recipient, $message) {
        // SMS API call here
        echo "SMS sent to: {$recipient} - {$message}\n";
    }
}

class FileStorage implements StorageInterface {
    public function save($identifier, $content) {
        file_put_contents("{$identifier}.txt", $content);
        return $identifier;
    }
}

class DatabaseStorage implements StorageInterface {
    public function save($identifier, $content) {
        // Database save logic
        echo "Saved to database: {$identifier}\n";
        return $identifier;
    }
}

// Usage - Dependency Injection in action!
$data = ['sales' => 100, 'users' => 50];

// Email + File combination
$emailFileReport = new GoodReportGenerator(
    new EmailNotification(),
    new FileStorage()
);
$emailFileReport->generateAndSend($data);

// SMS + Database combination
$smsDatabaseReport = new GoodReportGenerator(
    new SmsNotification(),
    new DatabaseStorage()
);
$smsDatabaseReport->generateAndSend($data);

// Easy to test with mocks!
class MockNotification implements NotificationInterface {
    public $lastMessage;
    
    public function send($recipient, $message) {
        $this->lastMessage = $message;
        echo "Mock: Message captured\n";
    }
}

$testReport = new GoodReportGenerator(
    new MockNotification(),
    new FileStorage()
);
$testReport->generateAndSend($data);
?>
```

### Simple Example - User Authentication

```php
<?php
// Interface for authentication
interface AuthenticatorInterface {
    public function authenticate($username, $password);
    public function isLoggedIn($userId);
}

interface UserRepositoryInterface {
    public function findByUsername($username);
    public function updateLastLogin($userId);
}

// High-level module - doesn't care about implementation details
class LoginService {
    private $authenticator;
    private $userRepository;
    
    public function __construct(
        AuthenticatorInterface $authenticator,
        UserRepositoryInterface $userRepository
    ) {
        $this->authenticator = $authenticator;
        $this->userRepository = $userRepository;
    }
    
    public function login($username, $password) {
        // Business logic - independent of implementation details
        $user = $this->userRepository->findByUsername($username);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        if ($this->authenticator->authenticate($username, $password)) {
            $this->userRepository->updateLastLogin($user['id']);
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
}

// Low-level implementations
class DatabaseAuthenticator implements AuthenticatorInterface {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function authenticate($username, $password) {
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $stored = $stmt->fetchColumn();
        
        return $stored && password_verify($password, $stored);
    }
    
    public function isLoggedIn($userId) {
        // Check session or token
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId;
    }
}

class LdapAuthenticator implements AuthenticatorInterface {
    private $ldapConnection;
    
    public function __construct($ldapConnection) {
        $this->ldapConnection = $ldapConnection;
    }
    
    public function authenticate($username, $password) {
        // LDAP authentication logic
        return ldap_bind($this->ldapConnection, $username, $password);
    }
    
    public function isLoggedIn($userId) {
        return isset($_SESSION['ldap_user']) && $_SESSION['ldap_user'] == $userId;
    }
}

class DatabaseUserRepository implements UserRepositoryInterface {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function updateLastLogin($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
}

// Usage - Easy to switch authentication methods
$pdo = new PDO('sqlite::memory:');

// Database authentication
$dbLogin = new LoginService(
    new DatabaseAuthenticator($pdo),
    new DatabaseUserRepository($pdo)
);

// LDAP authentication (same business logic!)
$ldapLogin = new LoginService(
    new LdapAuthenticator($ldapConnection),
    new DatabaseUserRepository($pdo)
);

// Both work exactly the same way
$result1 = $dbLogin->login('john', 'password123');
$result2 = $ldapLogin->login('jane', 'ldappassword');
?>
```

## 3. Use Cases

### Common DIP Scenarios:

1. **Database Access** - Repository pattern (MySQL, PostgreSQL, MongoDB)
2. **External APIs** - Payment gateways, social media, weather services
3. **File Systems** - Local storage, cloud storage (S3, Google Drive)
4. **Notifications** - Email, SMS, push notifications, Slack
5. **Authentication** - Local, OAuth, LDAP, Active Directory
6. **Caching** - Redis, Memcached, file cache, database cache
7. **Logging** - File logs, database logs, external services
8. **Queue Systems** - Redis queues, database queues, AWS SQS

### When DIP is Critical:

✅ **Testing** - Need to inject mock dependencies
✅ **Configuration** - Different implementations per environment
✅ **Plugin Systems** - Allow third-party implementations
✅ **Microservices** - Services should be swappable
✅ **Legacy Integration** - Gradually replace old systems

## 4. Real-Life Laravel Implementation

### Scenario: E-commerce Order Processing System

```php
<?php
// app/Contracts/OrderRepositoryInterface.php
namespace App\Contracts;

interface OrderRepositoryInterface
{
    public function create(array $orderData);
    public function findById($id);
    public function updateStatus($id, $status);
    public function findByUserId($userId);
}

// app/Contracts/PaymentProcessorInterface.php
namespace App\Contracts;

interface PaymentProcessorInterface
{
    public function processPayment($amount, $paymentData);
    public function refundPayment($transactionId, $amount);
    public function verifyPayment($transactionId);
}

// app/Contracts/NotificationServiceInterface.php
namespace App\Contracts;

interface NotificationServiceInterface
{
    public function sendOrderConfirmation($order, $customer);
    public function sendPaymentNotification($order, $paymentResult);
    public function sendShippingNotification($order, $trackingInfo);
}

// app/Contracts/InventoryManagerInterface.php
namespace App\Contracts;

interface InventoryManagerInterface
{
    public function checkAvailability($productId, $quantity)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return false;
        }
        
        return $product->stock_quantity >= $quantity;
    }
    
    public function reserveItems($items)
    {
        $reservationId = uniqid('res_');
        
        foreach ($items as $item) {
            InventoryReservation::create([
                'reservation_id' => $reservationId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'expires_at' => now()->addMinutes(15) // 15-minute reservation
            ]);
        }
        
        return $reservationId;
    }
    
    public function releaseReservation($reservationId)
    {
        return InventoryReservation::where('reservation_id', $reservationId)->delete();
    }
    
    public function updateStock($productId, $quantity)
    {
        return Product::where('id', $productId)
                      ->increment('stock_quantity', $quantity);
    }
}

// app/Providers/OrderServiceProvider.php - DEPENDENCY INJECTION SETUP
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\PaymentProcessorInterface;
use App\Contracts\NotificationServiceInterface;
use App\Contracts\InventoryManagerInterface;
use App\Repositories\EloquentOrderRepository;
use App\Services\StripePaymentProcessor;
use App\Services\MultiChannelNotificationService;
use App\Services\DatabaseInventoryManager;
use App\Services\OrderProcessingService;

class OrderServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind interfaces to implementations - DIP in action!
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(PaymentProcessorInterface::class, StripePaymentProcessor::class);
        $this->app->bind(NotificationServiceInterface::class, MultiChannelNotificationService::class);
        $this->app->bind(InventoryManagerInterface::class, DatabaseInventoryManager::class);
        
        // The high-level service gets all dependencies injected automatically
        $this->app->singleton(OrderProcessingService::class, function ($app) {
            return new OrderProcessingService(
                $app->make(OrderRepositoryInterface::class),
                $app->make(PaymentProcessorInterface::class),
                $app->make(NotificationServiceInterface::class),
                $app->make(InventoryManagerInterface::class)
            );
        });
    }
}

// app/Http/Controllers/OrderController.php
namespace App\Http\Controllers;

use App\Services\OrderProcessingService;
use App\Http\Requests\CreateOrderRequest;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private $orderProcessingService;
    
    // DIP: Controller depends on abstraction through service
    public function __construct(OrderProcessingService $orderProcessingService)
    {
        $this->orderProcessingService = $orderProcessingService;
    }
    
    public function store(CreateOrderRequest $request)
    {
        try {
            $result = $this->orderProcessingService->processOrder(
                $request->validated()['order'],
                $request->validated()['payment']
            );
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order processed successfully',
                    'order' => $result['order'],
                    'payment' => $result['payment_result']
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Order processing failed',
                    'error' => $result['error']
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function refund(Request $request, $orderId)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0'
        ]);
        
        try {
            $result = $this->orderProcessingService->refundOrder(
                $orderId,
                $request->amount
            );
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Refund processed' : 'Refund failed',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
```

## 5. Real-Life Laravel Code - Alternative Implementations

### Easy Implementation Switching with DIP

```php
<?php
// Alternative Payment Processor - PayPal
// app/Services/PayPalPaymentProcessor.php
namespace App\Services;

use App\Contracts\PaymentProcessorInterface;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;

class PayPalPaymentProcessor implements PaymentProcessorInterface
{
    private $apiContext;
    
    public function __construct()
    {
        $this->apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                config('services.paypal.client_id'),
                config('services.paypal.client_secret')
            )
        );
    }
    
    public function processPayment($amount, $paymentData)
    {
        try {
            $payer = new Payer();
            $payer->setPaymentMethod('paypal');
            
            $amountObj = new Amount();
            $amountObj->setCurrency('USD')->setTotal($amount);
            
            $transaction = new Transaction();
            $transaction->setAmount($amountObj)->setDescription('Order payment');
            
            $payment = new Payment();
            $payment->setIntent('sale')
                   ->setPayer($payer)
                   ->setTransactions(array($transaction));
            
            $payment->create($this->apiContext);
            
            return [
                'success' => true,
                'transaction_id' => $payment->getId(),
                'status' => 'pending',
                'amount' => $amount,
                'approval_url' => $this->getApprovalUrl($payment)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount)
    {
        try {
            // PayPal refund implementation
            $refundId = 'paypal_refund_' . uniqid();
            
            return [
                'success' => true,
                'refund_id' => $refundId,
                'amount' => $amount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyPayment($transactionId)
    {
        try {
            $payment = Payment::get($transactionId, $this->apiContext);
            
            return [
                'success' => true,
                'status' => $payment->getState(),
                'verified' => $payment->getState() === 'approved'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function getApprovalUrl($payment)
    {
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() === 'approval_url') {
                return $link->getHref();
            }
        }
        return null;
    }
}

// Alternative Notification Service - Slack Integration
// app/Services/SlackNotificationService.php
namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use GuzzleHttp\Client;

class SlackNotificationService implements NotificationServiceInterface
{
    private $httpClient;
    private $webhookUrl;
    
    public function __construct()
    {
        $this->httpClient = new Client();
        $this->webhookUrl = config('services.slack.webhook_url');
    }
    
    public function sendOrderConfirmation($order, $customer)
    {
        $message = [
            'text' => 'New Order Confirmation',
            'attachments' => [
                [
                    'color' => 'good',
                    'fields' => [
                        [
                            'title' => 'Order ID',
                            'value' => $order->id,
                            'short' => true
                        ],
                        [
                            'title' => 'Customer',
                            'value' => $customer['name'],
                            'short' => true
                        ],
                        [
                            'title' => 'Amount',
                            'value' => ';
    public function reserveItems($items);
    public function releaseReservation($reservationId);
    public function updateStock($productId, $quantity);
}

// app/Services/OrderProcessingService.php - HIGH-LEVEL MODULE
namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\PaymentProcessorInterface;
use App\Contracts\NotificationServiceInterface;
use App\Contracts\InventoryManagerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessingService
{
    private $orderRepository;
    private $paymentProcessor;
    private $notificationService;
    private $inventoryManager;
    
    // DIP: Depend on abstractions, not concretions
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentProcessorInterface $paymentProcessor,
        NotificationServiceInterface $notificationService,
        InventoryManagerInterface $inventoryManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentProcessor = $paymentProcessor;
        $this->notificationService = $notificationService;
        $this->inventoryManager = $inventoryManager;
    }
    
    public function processOrder(array $orderData, array $paymentData)
    {
        DB::beginTransaction();
        
        try {
            // Step 1: Validate inventory
            $this->validateInventory($orderData['items']);
            
            // Step 2: Reserve inventory
            $reservationId = $this->inventoryManager->reserveItems($orderData['items']);
            
            // Step 3: Create order
            $order = $this->orderRepository->create([
                ...$orderData,
                'status' => 'pending',
                'reservation_id' => $reservationId
            ]);
            
            // Step 4: Process payment
            $paymentResult = $this->paymentProcessor->processPayment(
                $orderData['total_amount'],
                $paymentData
            );
            
            if (!$paymentResult['success']) {
                $this->inventoryManager->releaseReservation($reservationId);
                throw new \Exception('Payment failed: ' . $paymentResult['error']);
            }
            
            // Step 5: Update order status
            $this->orderRepository->updateStatus($order->id, 'confirmed');
            
            // Step 6: Update inventory
            foreach ($orderData['items'] as $item) {
                $this->inventoryManager->updateStock($item['product_id'], -$item['quantity']);
            }
            
            // Step 7: Send notifications
            $this->notificationService->sendOrderConfirmation($order, $orderData['customer']);
            $this->notificationService->sendPaymentNotification($order, $paymentResult);
            
            DB::commit();
            
            Log::info('Order processed successfully', [
                'order_id' => $order->id,
                'customer_id' => $orderData['customer']['id'],
                'amount' => $orderData['total_amount']
            ]);
            
            return [
                'success' => true,
                'order' => $order,
                'payment_result' => $paymentResult
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Order processing failed', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundOrder($orderId, $amount = null)
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \Exception('Order not found');
        }
        
        $refundAmount = $amount ?? $order->total_amount;
        
        $refundResult = $this->paymentProcessor->refundPayment(
            $order->payment_transaction_id,
            $refundAmount
        );
        
        if ($refundResult['success']) {
            $this->orderRepository->updateStatus($orderId, 'refunded');
            
            // Return items to inventory if full refund
            if ($refundAmount == $order->total_amount) {
                foreach ($order->items as $item) {
                    $this->inventoryManager->updateStock($item->product_id, $item->quantity);
                }
            }
        }
        
        return $refundResult;
    }
    
    private function validateInventory($items)
    {
        foreach ($items as $item) {
            if (!$this->inventoryManager->checkAvailability($item['product_id'], $item['quantity'])) {
                throw new \Exception("Insufficient stock for product: {$item['product_id']}");
            }
        }
    }
}

// app/Repositories/EloquentOrderRepository.php - LOW-LEVEL MODULE
namespace App\Repositories;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $orderData)
    {
        return Order::create($orderData);
    }
    
    public function findById($id)
    {
        return Order::with(['items', 'customer'])->find($id);
    }
    
    public function updateStatus($id, $status)
    {
        return Order::where('id', $id)->update(['status' => $status]);
    }
    
    public function findByUserId($userId)
    {
        return Order::where('user_id', $userId)
                   ->with(['items'])
                   ->orderBy('created_at', 'desc')
                   ->get();
    }
}

// app/Services/StripePaymentProcessor.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\PaymentProcessorInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    
    public function processPayment($amount, $paymentData)
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $amount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount)
    {
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $transactionId,
                'amount' => $amount * 100
            ]);
            
            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $amount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyPayment($transactionId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($transactionId);
            
            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'verified' => $paymentIntent->status === 'succeeded'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// app/Services/MultiChannelNotificationService.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\PaymentNotification;
use App\Notifications\ShippingNotification;

class MultiChannelNotificationService implements NotificationServiceInterface
{
    public function sendOrderConfirmation($order, $customer)
    {
        try {
            // Email notification
            Mail::to($customer['email'])->send(
                new \App\Mail\OrderConfirmationMail($order, $customer)
            );
            
            // SMS notification if phone provided
            if (!empty($customer['phone'])) {
                // SMS implementation here
                $this->sendSms($customer['phone'], "Order #{$order->id} confirmed!");
            }
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'customer_email' => $customer['email'],
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function sendPaymentNotification($order, $paymentResult)
    {
        try {
            $message = $paymentResult['success'] 
                ? "Payment of ${$paymentResult['amount']} processed successfully"
                : "Payment failed: {$paymentResult['error']}";
            
            // Send via multiple channels
            Mail::to($order->customer_email)->send(
                new \App\Mail\PaymentNotificationMail($order, $paymentResult)
            );
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send payment notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function sendShippingNotification($order, $trackingInfo)
    {
        try {
            Mail::to($order->customer_email)->send(
                new \App\Mail\ShippingNotificationMail($order, $trackingInfo)
            );
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send shipping notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    private function sendSms($phone, $message)
    {
        // SMS implementation (Twilio, etc.)
        \Log::info("SMS sent to {$phone}: {$message}");
    }
}

// app/Services/DatabaseInventoryManager.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\InventoryManagerInterface;
use App\Models\Product;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;

class DatabaseInventoryManager implements InventoryManagerInterface
{
    public function checkAvailability($productId, $quantity) . number_format($order->total_amount, 2),
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->sendSlackMessage($message);
    }
    
    public function sendPaymentNotification($order, $paymentResult)
    {
        $color = $paymentResult['success'] ? 'good' : 'danger';
        $status = $paymentResult['success'] ? 'Success' : 'Failed';
        
        $message = [
            'text' => 'Payment Notification',
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Order ID',
                            'value' => $order->id,
                            'short' => true
                        ],
                        [
                            'title' => 'Status',
                            'value' => $status,
                            'short' => true
                        ],
                        [
                            'title' => 'Amount',
                            'value' => ';
    public function reserveItems($items);
    public function releaseReservation($reservationId);
    public function updateStock($productId, $quantity);
}

// app/Services/OrderProcessingService.php - HIGH-LEVEL MODULE
namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\PaymentProcessorInterface;
use App\Contracts\NotificationServiceInterface;
use App\Contracts\InventoryManagerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessingService
{
    private $orderRepository;
    private $paymentProcessor;
    private $notificationService;
    private $inventoryManager;
    
    // DIP: Depend on abstractions, not concretions
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentProcessorInterface $paymentProcessor,
        NotificationServiceInterface $notificationService,
        InventoryManagerInterface $inventoryManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentProcessor = $paymentProcessor;
        $this->notificationService = $notificationService;
        $this->inventoryManager = $inventoryManager;
    }
    
    public function processOrder(array $orderData, array $paymentData)
    {
        DB::beginTransaction();
        
        try {
            // Step 1: Validate inventory
            $this->validateInventory($orderData['items']);
            
            // Step 2: Reserve inventory
            $reservationId = $this->inventoryManager->reserveItems($orderData['items']);
            
            // Step 3: Create order
            $order = $this->orderRepository->create([
                ...$orderData,
                'status' => 'pending',
                'reservation_id' => $reservationId
            ]);
            
            // Step 4: Process payment
            $paymentResult = $this->paymentProcessor->processPayment(
                $orderData['total_amount'],
                $paymentData
            );
            
            if (!$paymentResult['success']) {
                $this->inventoryManager->releaseReservation($reservationId);
                throw new \Exception('Payment failed: ' . $paymentResult['error']);
            }
            
            // Step 5: Update order status
            $this->orderRepository->updateStatus($order->id, 'confirmed');
            
            // Step 6: Update inventory
            foreach ($orderData['items'] as $item) {
                $this->inventoryManager->updateStock($item['product_id'], -$item['quantity']);
            }
            
            // Step 7: Send notifications
            $this->notificationService->sendOrderConfirmation($order, $orderData['customer']);
            $this->notificationService->sendPaymentNotification($order, $paymentResult);
            
            DB::commit();
            
            Log::info('Order processed successfully', [
                'order_id' => $order->id,
                'customer_id' => $orderData['customer']['id'],
                'amount' => $orderData['total_amount']
            ]);
            
            return [
                'success' => true,
                'order' => $order,
                'payment_result' => $paymentResult
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Order processing failed', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundOrder($orderId, $amount = null)
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \Exception('Order not found');
        }
        
        $refundAmount = $amount ?? $order->total_amount;
        
        $refundResult = $this->paymentProcessor->refundPayment(
            $order->payment_transaction_id,
            $refundAmount
        );
        
        if ($refundResult['success']) {
            $this->orderRepository->updateStatus($orderId, 'refunded');
            
            // Return items to inventory if full refund
            if ($refundAmount == $order->total_amount) {
                foreach ($order->items as $item) {
                    $this->inventoryManager->updateStock($item->product_id, $item->quantity);
                }
            }
        }
        
        return $refundResult;
    }
    
    private function validateInventory($items)
    {
        foreach ($items as $item) {
            if (!$this->inventoryManager->checkAvailability($item['product_id'], $item['quantity'])) {
                throw new \Exception("Insufficient stock for product: {$item['product_id']}");
            }
        }
    }
}

// app/Repositories/EloquentOrderRepository.php - LOW-LEVEL MODULE
namespace App\Repositories;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $orderData)
    {
        return Order::create($orderData);
    }
    
    public function findById($id)
    {
        return Order::with(['items', 'customer'])->find($id);
    }
    
    public function updateStatus($id, $status)
    {
        return Order::where('id', $id)->update(['status' => $status]);
    }
    
    public function findByUserId($userId)
    {
        return Order::where('user_id', $userId)
                   ->with(['items'])
                   ->orderBy('created_at', 'desc')
                   ->get();
    }
}

// app/Services/StripePaymentProcessor.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\PaymentProcessorInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    
    public function processPayment($amount, $paymentData)
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $amount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount)
    {
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $transactionId,
                'amount' => $amount * 100
            ]);
            
            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $amount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyPayment($transactionId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($transactionId);
            
            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'verified' => $paymentIntent->status === 'succeeded'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// app/Services/MultiChannelNotificationService.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\PaymentNotification;
use App\Notifications\ShippingNotification;

class MultiChannelNotificationService implements NotificationServiceInterface
{
    public function sendOrderConfirmation($order, $customer)
    {
        try {
            // Email notification
            Mail::to($customer['email'])->send(
                new \App\Mail\OrderConfirmationMail($order, $customer)
            );
            
            // SMS notification if phone provided
            if (!empty($customer['phone'])) {
                // SMS implementation here
                $this->sendSms($customer['phone'], "Order #{$order->id} confirmed!");
            }
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'customer_email' => $customer['email'],
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function sendPaymentNotification($order, $paymentResult)
    {
        try {
            $message = $paymentResult['success'] 
                ? "Payment of ${$paymentResult['amount']} processed successfully"
                : "Payment failed: {$paymentResult['error']}";
            
            // Send via multiple channels
            Mail::to($order->customer_email)->send(
                new \App\Mail\PaymentNotificationMail($order, $paymentResult)
            );
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send payment notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function sendShippingNotification($order, $trackingInfo)
    {
        try {
            Mail::to($order->customer_email)->send(
                new \App\Mail\ShippingNotificationMail($order, $trackingInfo)
            );
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send shipping notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    private function sendSms($phone, $message)
    {
        // SMS implementation (Twilio, etc.)
        \Log::info("SMS sent to {$phone}: {$message}");
    }
}

// app/Services/DatabaseInventoryManager.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\InventoryManagerInterface;
use App\Models\Product;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;

class DatabaseInventoryManager implements InventoryManagerInterface
{
    public function checkAvailability($productId, $quantity) . number_format($paymentResult['amount'], 2),
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->sendSlackMessage($message);
    }
    
    public function sendShippingNotification($order, $trackingInfo)
    {
        $message = [
            'text' => 'Shipping Notification',
            'attachments' => [
                [
                    'color' => 'warning',
                    'fields' => [
                        [
                            'title' => 'Order ID',
                            'value' => $order->id,
                            'short' => true
                        ],
                        [
                            'title' => 'Tracking Number',
                            'value' => $trackingInfo['tracking_number'],
                            'short' => true
                        ],
                        [
                            'title' => 'Carrier',
                            'value' => $trackingInfo['carrier'],
                            'short' => true
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->sendSlackMessage($message);
    }
    
    private function sendSlackMessage($message)
    {
        try {
            $response = $this->httpClient->post($this->webhookUrl, [
                'json' => $message
            ]);
            
            return $response->getStatusCode() === 200;
            
        } catch (\Exception $e) {
            \Log::error('Slack notification failed', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
            
            return false;
        }
    }
}

// Alternative Inventory Manager - Redis-based
// app/Services/RedisInventoryManager.php
namespace App\Services;

use App\Contracts\InventoryManagerInterface;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;

class RedisInventoryManager implements InventoryManagerInterface
{
    private $redis;
    
    public function __construct()
    {
        $this->redis = Redis::connection();
    }
    
    public function checkAvailability($productId, $quantity)
    {
        $currentStock = $this->redis->get("product:stock:{$productId}");
        
        if ($currentStock === null) {
            // Sync from database if not in Redis
            $product = Product::find($productId);
            if ($product) {
                $currentStock = $product->stock_quantity;
                $this->redis->set("product:stock:{$productId}", $currentStock);
            } else {
                return false;
            }
        }
        
        return (int)$currentStock >= $quantity;
    }
    
    public function reserveItems($items)
    {
        $reservationId = uniqid('redis_res_');
        
        foreach ($items as $item) {
            $reservationKey = "reservation:{$reservationId}:{$item['product_id']}";
            
            // Store reservation with expiry
            $this->redis->setex($reservationKey, 900, $item['quantity']); // 15 minutes
            
            // Decrease available stock temporarily
            $this->redis->decrby("product:stock:{$item['product_id']}", $item['quantity']);
        }
        
        return $reservationId;
    }
    
    public function releaseReservation($reservationId)
    {
        $pattern = "reservation:{$reservationId}:*";
        $reservationKeys = $this->redis->keys($pattern);
        
        foreach ($reservationKeys as $key) {
            $quantity = $this->redis->get($key);
            $productId = explode(':', $key)[2];
            
            // Return stock
            $this->redis->incrby("product:stock:{$productId}", $quantity);
            
            // Remove reservation
            $this->redis->del($key);
        }
        
        return true;
    }
    
    public function updateStock($productId, $quantity)
    {
        // Update Redis
        $newStock = $this->redis->incrby("product:stock:{$productId}", $quantity);
        
        // Sync to database asynchronously
        \Queue::push(function () use ($productId, $newStock) {
            Product::where('id', $productId)->update(['stock_quantity' => $newStock]);
        });
        
        return $newStock;
    }
}

// Environment-based Configuration
// app/Providers/OrderServiceProvider.php - Updated
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register()
    {
        // DIP: Easy switching based on configuration
        $this->registerRepositories();
        $this->registerPaymentProcessors();
        $this->registerNotificationServices();
        $this->registerInventoryManagers();
        
        // Main service remains unchanged regardless of implementations
        $this->app->singleton(OrderProcessingService::class, function ($app) {
            return new OrderProcessingService(
                $app->make(OrderRepositoryInterface::class),
                $app->make(PaymentProcessorInterface::class),
                $app->make(NotificationServiceInterface::class),
                $app->make(InventoryManagerInterface::class)
            );
        });
    }
    
    private function registerRepositories()
    {
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
    }
    
    private function registerPaymentProcessors()
    {
        $processor = config('services.payment.default', 'stripe');
        
        switch ($processor) {
            case 'paypal':
                $this->app->bind(PaymentProcessorInterface::class, PayPalPaymentProcessor::class);
                break;
            case 'stripe':
            default:
                $this->app->bind(PaymentProcessorInterface::class, StripePaymentProcessor::class);
                break;
        }
    }
    
    private function registerNotificationServices()
    {
        $service = config('services.notifications.default', 'email');
        
        switch ($service) {
            case 'slack':
                $this->app->bind(NotificationServiceInterface::class, SlackNotificationService::class);
                break;
            case 'email':
            default:
                $this->app->bind(NotificationServiceInterface::class, MultiChannelNotificationService::class);
                break;
        }
    }
    
    private function registerInventoryManagers()
    {
        $manager = config('services.inventory.default', 'database');
        
        switch ($manager) {
            case 'redis':
                $this->app->bind(InventoryManagerInterface::class, RedisInventoryManager::class);
                break;
            case 'database':
            default:
                $this->app->bind(InventoryManagerInterface::class, DatabaseInventoryManager::class);
                break;
        }
    }
}
```

### Testing with DIP - Easy Mocking

```php
<?php
// tests/Unit/OrderProcessingServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OrderProcessingService;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\PaymentProcessorInterface;
use App\Contracts\NotificationServiceInterface;
use App\Contracts\InventoryManagerInterface;
use Mockery;

class OrderProcessingServiceTest extends TestCase
{
    private $orderRepository;
    private $paymentProcessor;
    private $notificationService;
    private $inventoryManager;
    private $orderProcessingService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // DIP makes testing easy - we can mock all dependencies
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->paymentProcessor = Mockery::mock(PaymentProcessorInterface::class);
        $this->notificationService = Mockery::mock(NotificationServiceInterface::class);
        $this->inventoryManager = Mockery::mock(InventoryManagerInterface::class);
        
        $this->orderProcessingService = new OrderProcessingService(
            $this->orderRepository,
            $this->paymentProcessor,
            $this->notificationService,
            $this->inventoryManager
        );
    }
    
    public function test_successful_order_processing()
    {
        $orderData = [
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
                ['product_id' => 2, 'quantity' => 1]
            ],
            'total_amount' => 100.00,
            'customer' => ['id' => 1, 'email' => 'test@example.com']
        ];
        
        $paymentData = ['payment_method_id' => 'pm_test'];
        
        // Mock inventory check
        $this->inventoryManager
            ->shouldReceive('checkAvailability')
            ->andReturn(true);
        
        // Mock inventory reservation
        $this->inventoryManager
            ->shouldReceive('reserveItems')
            ->andReturn('reservation_123');
        
        // Mock order creation
        $mockOrder = (object)['id' => 1, 'status' => 'pending'];
        $this->orderRepository
            ->shouldReceive('create')
            ->andReturn($mockOrder);
        
        // Mock payment processing
        $this->paymentProcessor
            ->shouldReceive('processPayment')
            ->andReturn([
                'success' => true,
                'transaction_id' => 'txn_123',
                'amount' => 100.00
            ]);
        
        // Mock order status update
        $this->orderRepository
            ->shouldReceive('updateStatus')
            ->andReturn(true);
        
        // Mock inventory update
        $this->inventoryManager
            ->shouldReceive('updateStock')
            ->andReturn(true);
        
        // Mock notifications
        $this->notificationService
            ->shouldReceive('sendOrderConfirmation')
            ->andReturn(true);
        
        $this->notificationService
            ->shouldReceive('sendPaymentNotification')
            ->andReturn(true);
        
        // Test the service
        $result = $this->orderProcessingService->processOrder($orderData, $paymentData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['order']->id);
    }
    
    public function test_order_processing_with_payment_failure()
    {
        $orderData = [
            'items' => [['product_id' => 1, 'quantity' => 1]],
            'total_amount' => 50.00,
            'customer' => ['id' => 1, 'email' => 'test@example.com']
        ];
        
        $paymentData = ['payment_method_id' => 'pm_fail'];
        
        // Mock successful inventory operations
        $this->inventoryManager
            ->shouldReceive('checkAvailability')
            ->andReturn(true);
        
        $this->inventoryManager
            ->shouldReceive('reserveItems')
            ->andReturn('reservation_123');
        
        // Mock order creation
        $mockOrder = (object)['id' => 1, 'status' => 'pending'];
        $this->orderRepository
            ->shouldReceive('create')
            ->andReturn($mockOrder);
        
        // Mock payment failure
        $this->paymentProcessor
            ->shouldReceive('processPayment')
            ->andReturn([
                'success' => false,
                'error' => 'Card declined'
            ]);
        
        // Mock reservation release
        $this->inventoryManager
            ->shouldReceive('releaseReservation')
            ->with('reservation_123')
            ->andReturn(true);
        
        // Test the service
        $result = $this->orderProcessingService->processOrder($orderData, $paymentData);
        
        $this->assertFalse($result['success']);
        $this->assertStringContains('Payment failed', $result['error']);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Configuration Files

```php
<?php
// config/services.php
return [
    'payment' => [
        'default' => env('PAYMENT_PROCESSOR', 'stripe'),
    ],
    
    'notifications' => [
        'default' => env('NOTIFICATION_SERVICE', 'email'),
    ],
    
    'inventory' => [
        'default' => env('INVENTORY_MANAGER', 'database'),
    ],
    
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'publishable' => env('STRIPE_PUBLISHABLE'),
    ],
    
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],
    
    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
    ],
];

// .env configurations for different environments
// .env.local
PAYMENT_PROCESSOR=stripe
NOTIFICATION_SERVICE=email
INVENTORY_MANAGER=database

// .env.staging
PAYMENT_PROCESSOR=stripe
NOTIFICATION_SERVICE=slack
INVENTORY_MANAGER=redis

// .env.production
PAYMENT_PROCESSOR=stripe
NOTIFICATION_SERVICE=email
INVENTORY_MANAGER=database
```

### Routes and Frontend Integration

```php
<?php
// routes/api.php
use App\Http\Controllers\OrderController;

Route::prefix('orders')->middleware('auth:api')->group(function () {
    Route::post('/', [OrderController::class, 'store']);
    Route::post('/{order}/refund', [OrderController::class, 'refund']);
    Route::get('/{order}', [OrderController::class, 'show']);
});

// Frontend JavaScript - Works regardless of backend implementations
class OrderProcessor {
    async processOrder(orderData, paymentData) {
        try {
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    order: orderData,
                    payment: paymentData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage('Order processed successfully!');
                return result;
            } else {
                this.showErrorMessage(result.message);
                throw new Error(result.error);
            }
            
        } catch (error) {
            this.showErrorMessage('Order processing failed');
            throw error;
        }
    }
    
    getAuthToken() {
        return localStorage.getItem('auth_token');
    }
    
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }
    
    showSuccessMessage(message) {
        console.log('Success:', message);
        // Show success UI
    }
    
    showErrorMessage(message) {
        console.error('Error:', message);
        // Show error UI
    }
}

// Usage
const processor = new OrderProcessor();

processor.processOrder({
    items: [
        { product_id: 1, quantity: 2, price: 25.00 },
        { product_id: 2, quantity: 1, price: 50.00 }
    ],
    total_amount: 100.00,
    customer: {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        phone: '+1234567890'
    }
}, {
    payment_method_id: 'pm_1234567890',
    billing_address: {
        street: '123 Main St',
        city: 'Anytown',
        state: 'CA',
        zip: '12345'
    }
}).then(result => {
    console.log('Order processed:', result);
}).catch(error => {
    console.error('Order failed:', error);
});
```

## Summary: DIP Benefits in Laravel

### ✅ Perfect Decoupling Achieved:

1. **High-Level Logic**: Independent of implementation details
2. **Easy Testing**: All dependencies can be mocked
3. **Configuration-Driven**: Switch implementations via config
4. **Environment Flexibility**: Different implementations per environment

### 🎯 Key DIP Patterns:

1. **Interface Contracts**: Define what, not how
2. **Dependency Injection**: Let container provide dependencies
3. **Service Providers**: Configure bindings in one place
4. **Constructor Injection**: Dependencies come from outside

### 🚀 Real-world Benefits:

- **Risk-free Switching**: Change payment processors without touching business logic
- **Easy Testing**: Mock all external dependencies
- **Team Independence**: Different teams work on different implementations
- **Gradual Migration**: Replace implementations one at a time
- **Environment Optimization**: Use different implementations for different needs

DIP transforms your Laravel applications from tightly-coupled monoliths into flexible, testable, and maintainable systems where business logic is completely independent of implementation details!;
    public function reserveItems($items);
    public function releaseReservation($reservationId);
    public function updateStock($productId, $quantity);
}

// app/Services/OrderProcessingService.php - HIGH-LEVEL MODULE
namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\PaymentProcessorInterface;
use App\Contracts\NotificationServiceInterface;
use App\Contracts\InventoryManagerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderProcessingService
{
    private $orderRepository;
    private $paymentProcessor;
    private $notificationService;
    private $inventoryManager;
    
    // DIP: Depend on abstractions, not concretions
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentProcessorInterface $paymentProcessor,
        NotificationServiceInterface $notificationService,
        InventoryManagerInterface $inventoryManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentProcessor = $paymentProcessor;
        $this->notificationService = $notificationService;
        $this->inventoryManager = $inventoryManager;
    }
    
    public function processOrder(array $orderData, array $paymentData)
    {
        DB::beginTransaction();
        
        try {
            // Step 1: Validate inventory
            $this->validateInventory($orderData['items']);
            
            // Step 2: Reserve inventory
            $reservationId = $this->inventoryManager->reserveItems($orderData['items']);
            
            // Step 3: Create order
            $order = $this->orderRepository->create([
                ...$orderData,
                'status' => 'pending',
                'reservation_id' => $reservationId
            ]);
            
            // Step 4: Process payment
            $paymentResult = $this->paymentProcessor->processPayment(
                $orderData['total_amount'],
                $paymentData
            );
            
            if (!$paymentResult['success']) {
                $this->inventoryManager->releaseReservation($reservationId);
                throw new \Exception('Payment failed: ' . $paymentResult['error']);
            }
            
            // Step 5: Update order status
            $this->orderRepository->updateStatus($order->id, 'confirmed');
            
            // Step 6: Update inventory
            foreach ($orderData['items'] as $item) {
                $this->inventoryManager->updateStock($item['product_id'], -$item['quantity']);
            }
            
            // Step 7: Send notifications
            $this->notificationService->sendOrderConfirmation($order, $orderData['customer']);
            $this->notificationService->sendPaymentNotification($order, $paymentResult);
            
            DB::commit();
            
            Log::info('Order processed successfully', [
                'order_id' => $order->id,
                'customer_id' => $orderData['customer']['id'],
                'amount' => $orderData['total_amount']
            ]);
            
            return [
                'success' => true,
                'order' => $order,
                'payment_result' => $paymentResult
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Order processing failed', [
                'error' => $e->getMessage(),
                'order_data' => $orderData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundOrder($orderId, $amount = null)
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \Exception('Order not found');
        }
        
        $refundAmount = $amount ?? $order->total_amount;
        
        $refundResult = $this->paymentProcessor->refundPayment(
            $order->payment_transaction_id,
            $refundAmount
        );
        
        if ($refundResult['success']) {
            $this->orderRepository->updateStatus($orderId, 'refunded');
            
            // Return items to inventory if full refund
            if ($refundAmount == $order->total_amount) {
                foreach ($order->items as $item) {
                    $this->inventoryManager->updateStock($item->product_id, $item->quantity);
                }
            }
        }
        
        return $refundResult;
    }
    
    private function validateInventory($items)
    {
        foreach ($items as $item) {
            if (!$this->inventoryManager->checkAvailability($item['product_id'], $item['quantity'])) {
                throw new \Exception("Insufficient stock for product: {$item['product_id']}");
            }
        }
    }
}

// app/Repositories/EloquentOrderRepository.php - LOW-LEVEL MODULE
namespace App\Repositories;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $orderData)
    {
        return Order::create($orderData);
    }
    
    public function findById($id)
    {
        return Order::with(['items', 'customer'])->find($id);
    }
    
    public function updateStatus($id, $status)
    {
        return Order::where('id', $id)->update(['status' => $status]);
    }
    
    public function findByUserId($userId)
    {
        return Order::where('user_id', $userId)
                   ->with(['items'])
                   ->orderBy('created_at', 'desc')
                   ->get();
    }
}

// app/Services/StripePaymentProcessor.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\PaymentProcessorInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripePaymentProcessor implements PaymentProcessorInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    
    public function processPayment($amount, $paymentData)
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $amount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount)
    {
        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $transactionId,
                'amount' => $amount * 100
            ]);
            
            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $amount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function verifyPayment($transactionId)
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($transactionId);
            
            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'verified' => $paymentIntent->status === 'succeeded'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// app/Services/MultiChannelNotificationService.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\PaymentNotification;
use App\Notifications\ShippingNotification;

class MultiChannelNotificationService implements NotificationServiceInterface
{
    public function sendOrderConfirmation($order, $customer)
    {
        try {
            // Email notification
            Mail::to($customer['email'])->send(
                new \App\Mail\OrderConfirmationMail($order, $customer)
            );
            
            // SMS notification if phone provided
            if (!empty($customer['phone'])) {
                // SMS implementation here
                $this->sendSms($customer['phone'], "Order #{$order->id} confirmed!");
            }
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'customer_email' => $customer['email'],
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function sendPaymentNotification($order, $paymentResult)
    {
        try {
            $message = $paymentResult['success'] 
                ? "Payment of ${$paymentResult['amount']} processed successfully"
                : "Payment failed: {$paymentResult['error']}";
            
            // Send via multiple channels
            Mail::to($order->customer_email)->send(
                new \App\Mail\PaymentNotificationMail($order, $paymentResult)
            );
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send payment notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function sendShippingNotification($order, $trackingInfo)
    {
        try {
            Mail::to($order->customer_email)->send(
                new \App\Mail\ShippingNotificationMail($order, $trackingInfo)
            );
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Failed to send shipping notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    private function sendSms($phone, $message)
    {
        // SMS implementation (Twilio, etc.)
        \Log::info("SMS sent to {$phone}: {$message}");
    }
}

// app/Services/DatabaseInventoryManager.php - LOW-LEVEL MODULE
namespace App\Services;

use App\Contracts\InventoryManagerInterface;
use App\Models\Product;
use App\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;

class DatabaseInventoryManager implements InventoryManagerInterface
{
    public function checkAvailability($productId, $quantity)
