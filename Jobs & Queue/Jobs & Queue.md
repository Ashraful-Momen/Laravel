# Laravel Jobs and Queue Tutorial

## Table of Contents
1. Introduction to Jobs and Queues
2. Queue Configuration
3. Creating and Dispatching Jobs
4. Job Handlers and Processing
5. Failed Jobs and Error Handling
6. Queue Workers and Supervision
7. Best Practices and Advanced Topics

## 1. Introduction to Jobs and Queues

Jobs and queues in Laravel allow you to defer time-consuming tasks to improve application performance. Common use cases include:
- Sending emails
- File processing
- External API calls
- Data imports/exports
- Image manipulation

### How Queues Work
A queue is like a todo list for your application. When you add a job to the queue:
1. The job is serialized and stored
2. A queue worker picks up the job
3. The job is processed in the background
4. Results are stored or notifications are sent

## 2. Queue Configuration

### Queue Drivers
Laravel supports multiple queue drivers:
```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'sync'),

'connections' => [
    'sync' => [
        'driver' => 'sync'
    ],
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
    ],
]
```

### Database Setup
If using the database driver, run:
```bash
php artisan queue:table
php artisan migrate
```

### Redis Setup
For Redis, install the package:
```bash
composer require predis/predis
```

## 3. Creating and Dispatching Jobs

### Generate a Job Class
```bash
php artisan make:job ProcessPodcast
```

### Job Class Structure
```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPodcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $podcast;

    public function __construct($podcast)
    {
        $this->podcast = $podcast;
    }

    public function handle()
    {
        // Process the podcast...
    }
}
```

### Dispatching Jobs
```php
// Basic dispatch
ProcessPodcast::dispatch($podcast);

// Chain jobs
ProcessPodcast::dispatch($podcast)
    ->onQueue('processing')
    ->delay(now()->addMinutes(10));

// Custom chain
Bus::chain([
    new ProcessPodcast($podcast),
    new OptimizePodcast($podcast),
    new ReleasePodcast($podcast),
])->dispatch();
```

## 4. Job Handlers and Processing

### The Handle Method
```php
public function handle()
{
    // Access constructor injected data
    $podcast = $this->podcast;

    // Access dependencies via type-hinting
    public function handle(AudioProcessor $processor)
    {
        $processor->process($this->podcast);
    }
}
```

### Job Middleware
```php
use Illuminate\Support\Facades\Redis;

class RateLimited
{
    public function handle($job, $next)
    {
        Redis::throttle('key')
            ->block(0)->allow(1)->every(5)
            ->then(function () use ($job, $next) {
                $next($job);
            }, function () use ($job) {
                $job->release(5);
            });
    }
}
```

## 5. Failed Jobs and Error Handling

### Failed Job Configuration
```php
// config/queue.php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'failed_jobs',
]
```

### Create Failed Jobs Table
```bash
php artisan queue:failed-table
php artisan migrate
```

### Handling Failed Jobs
```php
class ProcessPodcast implements ShouldQueue
{
    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 90;
    public $backoff = [2, 5, 10];

    public function failed(Throwable $exception)
    {
        // Notify team of failure...
    }
}
```

### Retry Failed Jobs
```bash
# Retry specific job
php artisan queue:retry 5
# Retry all failed jobs
php artisan queue:retry all
# Delete failed job
php artisan queue:forget 5
# Delete all failed jobs
php artisan queue:flush
```

## 6. Queue Workers and Supervision

### Running Queue Workers
```bash
# Basic worker
php artisan queue:work

# Specify connection and queue
php artisan queue:work redis --queue=high,default

# Run a single job
php artisan queue:work --once

# Specify attempts
php artisan queue:work --tries=3
```

### Supervisor Configuration
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/app.com/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=forge
numprocs=8
redirect_stderr=true
stdout_logfile=/home/forge/app.com/worker.log
stopwaitsecs=3600
```

### Deployment Considerations
```bash
# Put workers in maintenance mode
php artisan queue:restart

# Graceful worker shutdown
php artisan queue:restart --graceful
```

## 7. Best Practices and Advanced Topics

### Job Events
```php
Queue::before(function (JobProcessing $event) {
    // Handle job processing...
});

Queue::after(function (JobProcessed $event) {
    // Handle job processed...
});

Queue::failing(function (JobFailed $event) {
    // Handle job failure...
});
```

### Rate Limiting
```php
use Illuminate\Support\Facades\RateLimiter;

// In your job
public function handle()
{
    if (RateLimiter::tooManyAttempts('send-notification', 3)) {
        return $this->release(60);
    }

    RateLimiter::hit('send-notification');
    // Process job...
}
```

### Unique Jobs
```php
class ProcessPodcast implements ShouldQueue, ShouldBeUnique
{
    public $podcast;

    public function uniqueId()
    {
        return $this->podcast->id;
    }

    public function uniqueFor()
    {
        return 60; // Unique lock expires in 60 seconds
    }
}
```

### Job Batching
```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new ProcessPodcast($podcast1),
    new ProcessPodcast($podcast2),
])->then(function (Batch $batch) {
    // All jobs completed...
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure...
})->finally(function (Batch $batch) {
    // Batch completed...
})->dispatch();
```

### Performance Tips
1. Use appropriate queue drivers for your use case
2. Set reasonable timeout values
3. Implement proper error handling
4. Monitor queue metrics
5. Use job batching for large datasets
6. Implement rate limiting when needed
7. Keep jobs small and focused
8. Use job chaining for complex workflows
9. Regularly clean failed jobs table
10. Set up proper logging and monitoring

### Common Issues and Solutions
1. Memory leaks
   - Clear unused variables
   - Use garbage collection
   - Monitor memory usage

2. Timeouts
   - Set appropriate timeout values
   - Break large jobs into smaller chunks
   - Use job batching

3. Database connections
   - Reconnect if needed
   - Use database transactions
   - Handle deadlocks properly

4. Queue worker crashes
   - Set up proper supervision
   - Monitor worker processes
   - Implement graceful shutdown

Remember to always test your queued jobs thoroughly in a development environment before deploying to production. Monitor your queue performance and adjust configurations as needed based on your application's requirements.
