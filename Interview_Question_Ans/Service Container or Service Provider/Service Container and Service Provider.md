# Laravel Service Container & Service Provider Guide

## Service Container

The Service Container is one of Laravel's most powerful features. It's a tool for managing class dependencies and performing dependency injection.

### Key Concepts

1. **Binding**: Registering a class or interface into the container
2. **Resolution**: Retrieving an instance from the container
3. **Dependency Injection**: Automatic injection of dependencies

### Binding Services

#### Basic Binding

```php
// In a service provider
public function register()
{
    $this->app->bind('HelpSpot\API', function ($app) {
        return new \HelpSpot\API($app->make('HttpClient'));
    });
}
```

#### Singleton Binding

```php
// Register a singleton - same instance will be returned each time
$this->app->singleton('HelpSpot\API', function ($app) {
    return new \HelpSpot\API($app->make('HttpClient'));
});
```

#### Binding Interfaces to Implementations

```php
$this->app->bind(
    \App\Contracts\PaymentGatewayInterface::class,
    \App\Services\StripePaymentGateway::class
);
```

#### Contextual Binding

```php
$this->app->when(PhotoController::class)
          ->needs(Filesystem::class)
          ->give(function () {
              return new Filesystem(storage_path('photos'));
          });
```

### Resolving Services

#### Using the Container Directly

```php
// From container
$api = app('HelpSpot\API');
// Or typehint
$api = app(\HelpSpot\API::class);
// Or array access
$api = app()['HelpSpot\API'];
```

#### Automatic Resolution

Laravel's container can automatically resolve classes without explicit bindings if no unresolvable dependencies exist:

```php
class UserController
{
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }
}
```

The `UserRepository` will be automatically resolved.

#### Using the `make` Method

```php
$api = $this->app->make('HelpSpot\API');
```

#### Using the `resolve` Helper

```php
$api = resolve('HelpSpot\API');
```

### Container Events

```php
$this->app->resolving(function ($object, $app) {
    // Called when container resolves object of any type
});

$this->app->resolving(\HelpSpot\API::class, function ($api, $app) {
    // Called when container resolves objects of this type
});
```

### Practical Example: Repository Pattern

```php
// Interface
namespace App\Repositories\Contracts;

interface UserRepositoryInterface
{
    public function getAll();
    public function findById($id);
    public function create(array $data);
}

// Implementation
namespace App\Repositories;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    protected $model;
    
    public function __construct(User $user)
    {
        $this->model = $user;
    }
    
    public function getAll()
    {
        return $this->model->all();
    }
    
    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }
    
    public function create(array $data)
    {
        return $this->model->create($data);
    }
}

// Binding in a service provider
$this->app->bind(
    \App\Repositories\Contracts\UserRepositoryInterface::class,
    \App\Repositories\EloquentUserRepository::class
);

// Usage in a controller
class UserController extends Controller
{
    protected $users;
    
    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }
    
    public function index()
    {
        return $this->users->getAll();
    }
}
```

## Service Providers

Service Providers are the central place to configure your application. Almost all of Laravel's core services are bootstrapped via service providers.

### Service Provider Lifecycle

1. **Register**: Bind things into the service container
2. **Boot**: Called after all providers are registered

### Creating a Service Provider

```php
php artisan make:provider PaymentServiceProvider
```

This creates:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
```

### Registering a Provider

Add your service provider to the `providers` array in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\PaymentServiceProvider::class,
],
```

### Implementing a Complete Service Provider

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PaymentGateway;
use App\Services\PaymentProcessor;
use App\Contracts\PaymentGatewayInterface;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return new PaymentGateway(config('services.stripe.key'));
        });
        
        // Register singleton services
        $this->app->singleton('payment.processor', function ($app) {
            return new PaymentProcessor(
                $app->make(PaymentGatewayInterface::class),
                $app->make('logger')
            );
        });
        
        // Register configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/payment.php', 'payment'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../routes/payment.php');
        
        // Register views
        $this->loadViewsFrom(__DIR__.'/../resources/views/payment', 'payment');
        
        // Register migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/payment');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\ProcessPayments::class,
            ]);
        }
        
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/payment.php' => config_path('payment.php'),
        ], 'payment-config');
        
        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('verify.payment', \App\Http\Middleware\VerifyPayment::class);
        
        // Register event listeners
        $this->app['events']->listen(
            \App\Events\PaymentReceived::class,
            \App\Listeners\SendPaymentNotification::class
        );
    }
}
```

### Deferred Service Providers

If your service provider is only registering bindings in the service container, you may want to defer its registration until one of its bindings is actually needed:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Logger;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('logger', function ($app) {
            return new Logger();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['logger'];
    }
}
```

## Real-World Example: Custom Notification Service

This example demonstrates creating a service for sending notifications through multiple channels and registering it with Laravel:

```php
// app/Contracts/NotificationServiceInterface.php
namespace App\Contracts;

interface NotificationServiceInterface
{
    public function send($user, $message, $channels = []);
}

// app/Services/NotificationService.php
namespace App\Services;

use App\Contracts\NotificationServiceInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService implements NotificationServiceInterface
{
    protected $config;
    protected $defaultChannels;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultChannels = $config['default_channels'] ?? ['mail'];
    }
    
    public function send($user, $message, $channels = [])
    {
        $channels = !empty($channels) ? $channels : $this->defaultChannels;
        
        foreach ($channels as $channel) {
            $method = "sendVia" . ucfirst($channel);
            if (method_exists($this, $method)) {
                $this->$method($user, $message);
            } else {
                Log::warning("Channel {$channel} not supported.");
            }
        }
        
        return true;
    }
    
    protected function sendViaMail($user, $message)
    {
        Mail::raw($message, function ($mail) use ($user) {
            $mail->to($user->email)
                 ->subject('New Notification');
        });
    }
    
    protected function sendViaSms($user, $message)
    {
        // Implementation using a third-party SMS service
        $smsService = new \ThirdParty\SmsService($this->config['sms_api_key']);
        $smsService->send($user->phone, $message);
    }
    
    protected function sendViaPush($user, $message)
    {
        // Implementation using Firebase or another push notification service
        // ...
    }
}

// app/Providers/NotificationServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\NotificationServiceInterface;
use App\Services\NotificationService;

class NotificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/notification.php', 'notification'
        );
        
        // Register the notification service
        $this->app->singleton(NotificationServiceInterface::class, function ($app) {
            return new NotificationService($app['config']['notification']);
        });
        
        // Facade-like alias
        $this->app->alias(NotificationServiceInterface::class, 'notification');
    }
    
    public function boot()
    {
        // Publish the configuration
        $this->publishes([
            __DIR__.'/../config/notification.php' => config_path('notification.php'),
        ], 'notification-config');
        
        // Add macros to User model
        \App\Models\User::macro('notify', function ($message, $channels = []) {
            return app(NotificationServiceInterface::class)->send($this, $message, $channels);
        });
    }
}

// config/notification.php
return [
    'default_channels' => ['mail'],
    'sms_api_key' => env('SMS_API_KEY'),
    'push_enabled' => env('PUSH_NOTIFICATIONS_ENABLED', false),
];

// Usage in a controller
class UserController extends Controller
{
    protected $notificationService;
    
    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    public function sendWelcome($id)
    {
        $user = User::find($id);
        
        // Using the service directly
        $this->notificationService->send(
            $user, 
            'Welcome to our platform!', 
            ['mail', 'sms']
        );
        
        // Or using the macro on the User model
        $user->notify('Welcome to our platform!', ['mail', 'sms']);
        
        return back()->with('success', 'Welcome message sent!');
    }
}
```

## Advanced Container Features

### Tagged Services

```php
// Register services with tags
$this->app->bind('SpeedReport', function () {
    //
});

$this->app->bind('MemoryReport', function () {
    //
});

$this->app->tag(['SpeedReport', 'MemoryReport'], 'reports');

// Resolve all services with a given tag
$this->app->bind('ReportAggregator', function ($app) {
    return new ReportAggregator($app->tagged('reports'));
});
```

### Extending Services

```php
// Original binding
$this->app->bind('translator', function () {
    return new Translator;
});

// Extend the translator
$this->app->extend('translator', function ($translator, $app) {
    $translator->addLanguage('fr');
    
    return $translator;
});
```

### Container Facades

```php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Notification extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'notification';
    }
}

// Usage
use App\Facades\Notification;

Notification::send($user, 'Hello');
```

## Best Practices

1. **Keep your service providers focused**: Create separate providers for different aspects of your application.
2. **Use interfaces**: Bind interfaces to implementations, not concrete classes to other concrete classes.
3. **Utilize contextual binding**: When different implementations are needed in different scenarios.
4. **Leverage deferred loading**: Use deferred providers for services that aren't needed on every request.
5. **Be explicit**: While the container can auto-resolve many dependencies, explicit bindings make your application more maintainable.
6. **Document your services**: Add PHPDoc blocks to your service providers to document what they register.
