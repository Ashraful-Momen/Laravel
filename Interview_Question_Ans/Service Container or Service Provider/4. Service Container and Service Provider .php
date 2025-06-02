<?php

/*
===============================================================================
LARAVEL SERVICE CONTAINER এবং SERVICE PROVIDER - সম্পূর্ণ ব্যাখ্যা
===============================================================================

SERVICE CONTAINER কি?
----------------------
Service Container হলো Laravel এর একটি powerful IoC (Inversion of Control) container।
এটি একটি বাক্স বা কন্টেইনার যেখানে আমরা আমাদের services গুলো রাখি এবং প্রয়োজনের সময় বের করে নিই।

SERVICE PROVIDER কি?
--------------------
Service Provider হলো Laravel application এর central place যেখানে সব services bind করা হয়।
এটি বলতে পারেন Laravel এর bootstrap center।

কেন ব্যবহার করি?
-----------------
1. Dependency Management - নির্ভরতা নিয়ন্ত্রণ
2. Loose Coupling - শিথিল সংযোগ  
3. Easy Testing - সহজ পরীক্ষা
4. Code Reusability - কোড পুনর্ব্যবহার
*/

// ===============================================================================
// ১. SERVICE CONTAINER এর মূল ধারণা
// ===============================================================================

/*
SERVICE CONTAINER ALGORITHM:
---------------------------
1. Register Phase (নিবন্ধন পর্যায়)
   - Services গুলোকে container এ bind করা হয়
   - Interface to Implementation mapping তৈরি হয়

2. Resolution Phase (সমাধান পর্যায়)  
   - যখন service চাই, container dependency graph বানায়
   - Automatically dependencies inject করে
   - Object তৈরি করে return করে

CONTAINER এর কাজের ধাপ:
1. bind() - service register করা
2. resolve() - service বের করা  
3. make() - object তৈরি করা
4. singleton() - একটি instance তৈরি করা
*/

// Container এর মতো কাজ করে এভাবে:
class SimpleContainer 
{
    private $bindings = [];
    private $instances = [];

    // STEP 1: Service bind করা (কি দিয়ে কি বানাবো)
    public function bind($abstract, $concrete)
    {
        echo "🔗 Binding করা হচ্ছে: $abstract -> " . (is_string($concrete) ? $concrete : 'Closure') . "\n";
        $this->bindings[$abstract] = $concrete;
    }

    // STEP 2: Singleton bind করা (একবার বানিয়ে রাখা)  
    public function singleton($abstract, $concrete)
    {
        echo "🔒 Singleton Binding: $abstract\n";
        $this->bind($abstract, $concrete);
    }

    // STEP 3: Service resolve করা (চাওয়া মাত্র দেওয়া)
    public function resolve($abstract)
    {
        echo "🔍 Resolving: $abstract\n";
        
        // Singleton check
        if (isset($this->instances[$abstract])) {
            echo "✨ Singleton instance return করা হচ্ছে\n";
            return $this->instances[$abstract];
        }

        // Binding থেকে concrete বের করা
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // Object তৈরি করা
        if ($concrete instanceof Closure) {
            $object = $concrete($this);
        } else {
            $object = $this->build($concrete);
        }

        // Singleton হলে store করা
        if ($this->isSingleton($abstract)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    // STEP 4: Object build করা (Constructor dependency inject সহ)
    private function build($concrete)
    {
        echo "🏗️ Building: $concrete\n";
        
        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();

        // Constructor না থাকলে সরাসরি object return
        if (is_null($constructor)) {
            return new $concrete;
        }

        // Constructor এর parameters গুলো resolve করা
        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $dependencies[] = $this->resolveDependency($parameter);
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    // Dependency resolve করা  
    private function resolveDependency($parameter)
    {
        $type = $parameter->getType();
        if ($type && !$type->isBuiltin()) {
            echo "📦 Dependency resolve: " . $type->getName() . "\n";
            return $this->resolve($type->getName());
        }
        
        // Default value বা null return
        return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
    }

    private function isSingleton($abstract)
    {
        // Simplified singleton check
        return isset($this->bindings[$abstract]);
    }
}

// ===============================================================================
// ২. SERVICE PROVIDER এর structure এবং কেন register() ও boot() দুটো method
// ===============================================================================

/*
SERVICE PROVIDER LIFECYCLE:
---------------------------
1. REGISTER PHASE (নিবন্ধন পর্যায়)
   - সব service providers এর register() method call হয়
   - Services container এ bind করা হয়  
   - এখানে শুধু binding করি, কোন service use করি না

2. BOOT PHASE (চালু পর্যায়)  
   - সব providers register হওয়ার পর boot() call হয়
   - এখানে registered services use করতে পারি
   - Configuration, Event listeners setup করি

কেন দুটো method আলাদা?
-----------------------
register() - শুধু services register করার জন্য
boot() - registered services use করার জন্য

যদি register() এ অন্য service use করি তাহলে সেটি এখনো register হয়নি তাই error আসবে।
*/

// Base Service Provider class এর structure
abstract class BaseServiceProvider
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
        echo "🎯 ServiceProvider created\n";
    }

    // REGISTER METHOD - Services bind করার জন্য
    abstract public function register();

    // BOOT METHOD - Services use করার জন্য  
    public function boot() 
    {
        echo "🚀 ServiceProvider booted\n";
        // Override করতে হবে child class এ
    }
}

// ===============================================================================
// ৩. একটি সম্পূর্ণ SERVICE PROVIDER উদাহরণ
// ===============================================================================

// Email Service Interface
interface EmailServiceInterface 
{
    public function send(string $to, string $subject, string $message): bool;
}

// SMTP Email Service Implementation
class SMTPEmailService implements EmailServiceInterface
{
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        echo "📧 SMTPEmailService তৈরি হয়েছে\n";
        echo "Configuration: " . json_encode($config) . "\n";
    }

    public function send(string $to, string $subject, string $message): bool
    {
        echo "📤 SMTP দিয়ে Email পাঠানো হচ্ছে:\n";
        echo "To: $to\n";
        echo "Subject: $subject\n"; 
        echo "Message: $message\n";
        return true;
    }
}

// Log Service
class LogService 
{
    private $logFile;

    public function __construct(string $logFile = 'app.log')
    {
        $this->logFile = $logFile;
        echo "📝 LogService চালু হয়েছে - File: $logFile\n";
    }

    public function info(string $message)
    {
        echo "ℹ️ LOG: $message\n";
    }

    public function error(string $message)  
    {
        echo "❌ ERROR LOG: $message\n";
    }
}

// User Service যেটি EmailService এবং LogService ব্যবহার করে
class UserService
{
    private $emailService;
    private $logService;

    public function __construct(EmailServiceInterface $emailService, LogService $logService)
    {
        $this->emailService = $emailService;
        $this->logService = $logService;
        echo "👤 UserService তৈরি হয়েছে\n";
    }

    public function createUser(string $name, string $email): array
    {
        // User তৈরি করা
        $user = ['id' => rand(1, 1000), 'name' => $name, 'email' => $email];
        
        // Welcome email পাঠানো
        $this->emailService->send($email, 'স্বাগতম!', "হ্যালো $name, আপনার account তৈরি হয়েছে!");
        
        // Log করা
        $this->logService->info("User তৈরি হয়েছে: $name ($email)");
        
        return $user;
    }
}

// ===============================================================================  
// ৪. SERVICE PROVIDER তৈরি করা - REGISTER এবং BOOT এর ব্যবহার
// ===============================================================================

class EmailServiceProvider extends BaseServiceProvider
{
    /*
    REGISTER METHOD এর কাজ:
    -----------------------
    1. Services গুলোকে container এ bind করা
    2. Interface to Implementation mapping
    3. Configuration setup করা
    4. কোন service এখানে use করা যাবে না (এখনো সব register হয়নি)
    
    PARAMETERS EXPLANATION:
    ----------------------
    $this->app - এটি Service Container instance
    bind() - Simple binding করে (প্রতিবার নতুন instance)
    singleton() - Singleton binding (একটি instance সবার জন্য)
    when() - Contextual binding (বিশেষ ক্ষেত্রে বিশেষ implementation)
    */
    public function register()
    {
        echo "\n🔧 EmailServiceProvider REGISTER হচ্ছে...\n";
        
        // SIMPLE BINDING - প্রতিবার নতুন instance তৈরি হবে
        $this->app->bind(EmailServiceInterface::class, function($app) {
            echo "🏭 EmailService এর নতুন instance তৈরি হচ্ছে\n";
            
            // Configuration array তৈরি করা
            $config = [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'username' => 'test@gmail.com',
                'password' => 'password'
            ];
            
            return new SMTPEmailService($config);
        });

        // SINGLETON BINDING - একবার তৈরি হয়ে সবার জন্য same instance  
        $this->app->singleton(LogService::class, function($app) {
            echo "🔒 LogService Singleton তৈরি হচ্ছে\n";
            return new LogService('storage/logs/laravel.log');
        });

        // USER SERVICE BINDING - অন্য services depend করে
        $this->app->bind(UserService::class, function($app) {
            echo "👥 UserService তৈরি হচ্ছে dependencies সহ\n";
            
            // Container থেকে dependencies resolve করা
            $emailService = $app->make(EmailServiceInterface::class);
            $logService = $app->make(LogService::class);
            
            return new UserService($emailService, $logService);
        });

        echo "✅ সব services REGISTER হয়ে গেছে\n";
    }

    /*
    BOOT METHOD এর কাজ:
    -------------------  
    1. সব services register হওয়ার পর চলে
    2. এখানে registered services use করতে পারি
    3. Event listeners, middleware register করি
    4. Additional configuration করি
    
    কেন BOOT আলাদা?
    ----------------
    register() এ যদি কোন service use করি যেটি এখনো register হয়নি 
    তাহলে error আসবে। তাই boot() এ সব কাজ করি।
    */
    public function boot()
    {
        echo "\n🚀 EmailServiceProvider BOOT হচ্ছে...\n";
        
        // এখন registered services use করতে পারি
        $logService = $this->app->make(LogService::class);
        $logService->info('EmailServiceProvider boot হয়েছে');
        
        // Configuration publish করা (Laravel এ)
        // $this->publishes([
        //     __DIR__.'/config/email.php' => config_path('email.php'),
        // ]);
        
        echo "✅ EmailServiceProvider boot সম্পন্ন\n";
    }
}

// ===============================================================================
// ৫. APP.PHP এর মতো Application Bootstrap ক্লাস
// ===============================================================================

/*
APP.PHP এর কাজ:
---------------
1. Service Container তৈরি করা
2. Service Providers register করা  
3. Providers boot করা
4. Application ready করা

Laravel এর config/app.php এ 'providers' array এ সব Service Providers list থাকে।
Application boot হওয়ার সময় এই providers গুলো load হয়।
*/

class Application 
{
    private $container;
    private $providers = [];
    private $bootedProviders = [];

    public function __construct()
    {
        echo "\n🏁 Application শুরু হচ্ছে...\n";
        
        // Service Container তৈরি করা
        $this->container = new SimpleContainer();
        
        // নিজেকে container এ bind করা
        $this->container->singleton('app', function() {
            return $this;
        });
        
        echo "✅ Service Container তৈরি হয়েছে\n";
    }

    /*
    SERVICE PROVIDERS REGISTER করার ALGORITHM:
    ------------------------------------------
    1. Provider class instantiate করা
    2. register() method call করা  
    3. Provider list এ রাখা boot এর জন্য
    */
    public function registerProvider($providerClass)
    {
        echo "\n📋 Provider register করা হচ্ছে: $providerClass\n";
        
        // Provider instance তৈরি করা
        $provider = new $providerClass($this->container);
        
        // Register method call করা
        $provider->register();
        
        // Provider list এ রাখা boot এর জন্য
        $this->providers[] = $provider;
        
        echo "✅ $providerClass register সম্পন্ন\n";
    }

    /*
    SERVICE PROVIDERS BOOT করার ALGORITHM:
    --------------------------------------
    1. সব providers register হওয়ার পর boot শুরু
    2. প্রতিটি provider এর boot() method call করা
    3. Booted providers list এ রাখা
    */
    public function bootProviders()
    {
        echo "\n🚀 সব Providers BOOT করা হচ্ছে...\n";
        
        foreach ($this->providers as $provider) {
            if (!in_array($provider, $this->bootedProviders)) {
                $provider->boot();
                $this->bootedProviders[] = $provider;
            }
        }
        
        echo "✅ সব Providers BOOT সম্পন্ন\n";
    }

    // Service resolve করার method
    public function make($abstract)
    {
        return $this->container->resolve($abstract);
    }

    // Application চালু করা
    public function run()
    {
        echo "\n🎯 Application চালু হয়েছে এবং ব্যবহারের জন্য প্রস্তুত!\n";
        echo "এখন যেকোনো service use করতে পারেন।\n\n";
    }
}

// ===============================================================================
// ৬. CONFIG/APP.PHP এর মতো Configuration
// ===============================================================================

/*
Laravel এর config/app.php file এ যেভাবে providers array থাকে:

'providers' => [
    // Laravel Framework Service Providers...
    Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Broadcasting\BroadcastServiceProvider::class,
    
    // Application Service Providers...
    App\Providers\AppServiceProvider::class,
    App\Providers\EmailServiceProvider::class,
],

এই array থেকে Application bootstrap সময় providers load করে।
*/

class AppConfig
{
    public static function getProviders()
    {
        return [
            // আমাদের তৈরি Service Providers
            EmailServiceProvider::class,
            // অন্যান্য providers এখানে add করবেন
        ];
    }
}

// ===============================================================================
// ৭. সম্পূর্ণ BOOTSTRAP PROCESS - Laravel এর মতো
// ===============================================================================

echo "\n" . str_repeat("=", 80) . "\n";
echo "🚀 LARAVEL BOOTSTRAP PROCESS SIMULATION 🚀\n"; 
echo str_repeat("=", 80) . "\n";

// STEP 1: Application তৈরি করা
$app = new Application();

// STEP 2: Service Providers register করা (config/app.php থেকে)
echo "\n📋 REGISTRATION PHASE শুরু...\n";
$providers = AppConfig::getProviders();

foreach ($providers as $providerClass) {
    $app->registerProvider($providerClass);
}
echo "\n✅ সব providers REGISTER সম্পন্ন!\n";

// STEP 3: Providers boot করা  
$app->bootProviders();

// STEP 4: Application ready
$app->run();

// ===============================================================================
// ৮. SERVICES ব্যবহার করার উদাহরণ
// ===============================================================================

echo str_repeat("-", 80) . "\n";
echo "📝 SERVICES ব্যবহারের উদাহরণ:\n";
echo str_repeat("-", 80) . "\n";

// UserService resolve করা - Container automatically সব dependencies inject করবে
echo "\n🔍 UserService resolve করা হচ্ছে...\n";
$userService = $app->make(UserService::class);

echo "\n👤 User তৈরি করা হচ্ছে...\n";
$user = $userService->createUser('রহিম উদ্দিন', 'rahim@example.com');

echo "\n✨ তৈরি হওয়া User:\n";
print_r($user);

// আলাদা আলাদা services ও resolve করতে পারি
echo "\n📧 Email Service আলাদাভাবে resolve:\n";
$emailService = $app->make(EmailServiceInterface::class);
$emailService->send('karim@example.com', 'Test', 'এটি একটি test email');

echo "\n📝 Log Service আলাদাভাবে resolve:\n";  
$logService = $app->make(LogService::class);
$logService->info('Application সফলভাবে চালু হয়েছে');

// ===============================================================================
// ৯. DEPENDENCY INJECTION এর সুবিধা দেখানো
// ===============================================================================

echo "\n" . str_repeat("=", 80) . "\n";
echo "💡 DEPENDENCY INJECTION এর সুবিধা:\n";
echo str_repeat("=", 80) . "\n";

/*
১. LOOSE COUPLING (শিথিল সংযোগ):
--------------------------------
UserService EmailServiceInterface depend করে, concrete class এ না।
তাই implementation পরিবর্তন করলে UserService change করতে হবে না।

২. EASY TESTING (সহজ পরীক্ষা):  
-------------------------------
Test এ Mock EmailService inject করতে পারি।

৩. SINGLE RESPONSIBILITY (একক দায়িত্ব):
---------------------------------------
প্রতিটি class শুধু নিজের কাজ করে।

৪. CONFIGURATION MANAGEMENT:
---------------------------
সব configuration এক জায়গায় (Service Provider এ)।
*/

// Mock Email Service for Testing
class MockEmailService implements EmailServiceInterface
{
    public function send(string $to, string $subject, string $message): bool
    {
        echo "🧪 MOCK: Email পাঠানোর simulation - $to\n";
        return true;
    }
}

// Testing এর জন্য Mock service bind করা
echo "\n🧪 Testing এর জন্য Mock Service bind:\n";
$app->container->bind(EmailServiceInterface::class, function() {
    return new MockEmailService();
});

$testUserService = $app->make(UserService::class);
$testUser = $testUserService->createUser('Test User', 'test@example.com');

echo "\n" . str_repeat("=", 80) . "\n";
echo "🎉 LARAVEL SERVICE CONTAINER ও SERVICE PROVIDER COMPLETE! 🎉\n";
echo str_repeat("=", 80) . "\n";

/*
===============================================================================
সারসংক্ষেপ - ALGORITHM ও KEY POINTS:
===============================================================================

SERVICE CONTAINER ALGORITHM:
1. bind() - Service register করা
2. resolve() - Dependencies খুঁজে বের করা  
3. build() - Constructor dependencies inject করে object তৈরি
4. return - Ready object return করা

SERVICE PROVIDER LIFECYCLE:
1. register() - Services bind করা (অন্য service use করা যাবে না)
2. boot() - Services use করা (সব service এখন available)

APP.PHP এর কাজ:
1. Providers array define করা
2. Application bootstrap সময় providers load করা

কেন এই পদ্ধতি?
1. Loose Coupling - Classes আলাদা আলাদা
2. Easy Testing - Mock dependencies inject করা যায়
3. Maintainable Code - Change সহজ
4. Reusable Services - Services পুনর্ব্যবহার যোগ্য
5. Centralized Configuration - Configuration এক জায়গায়

BOOT কেন আলাদা?
register() এ অন্য service use করলে error কারণ সেটি এখনো register হয়নি।
boot() এ সব service available তাই safely use করা যায়।
===============================================================================
*/
