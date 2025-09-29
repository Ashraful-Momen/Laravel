<?php
/*
|==================================================================================
| LARAVEL TESTING – COMPLETE INTERVIEW CHEAT SHEET
|==================================================================================
| A comprehensive, copy-paste-ready reference covering all Laravel testing concepts
| commonly asked in technical interviews.
|==================================================================================
*/

/* ------------------------------------------------------------------
|  SECTION 0: ENVIRONMENT SETUP
|-------------------------------------------------------------------*/

// .env.testing configuration
APP_ENV=testing
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
DB_CONNECTION=sqlite
DB_DATABASE=:memory:          # In-memory DB (fastest)
# DB_DATABASE=db_test.sqlite  # Persistent test DB

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
SESSION_DRIVER=array
FILESYSTEM_DISK=testing

// Commands to run after setup:
php artisan config:clear
php artisan migrate --env=testing
php artisan test

/* ------------------------------------------------------------------
|  SECTION 1: TEST FOUNDATION
|-------------------------------------------------------------------*/

// Base Test Class (tests/TestCase.php)
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Global setup for all tests
    }
    
    protected function tearDown(): void
    {
        // Cleanup after each test
        parent::tearDown();
    }
}

/* ------------------------------------------------------------------
|  SECTION 2: ESSENTIAL TRAITS (Interview Question)
|-------------------------------------------------------------------*/

RefreshDatabase      // Migrates DB before each test, rolls back after
DatabaseTransactions // Wraps each test in a transaction, rolls back
DatabaseMigrations   // Runs migrations before each test
WithFaker           // Provides $this->faker for dummy data
WithoutMiddleware   // Disables all middleware
WithoutEvents       // Disables event dispatching
LazilyRefreshDatabase // Only migrates when DB queries are made

// Example usage:
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class UserTest extends TestCase
{
    use RefreshDatabase, WithFaker;
}

/* ------------------------------------------------------------------
|  SECTION 3: UNIT TESTS (Testing Logic Without HTTP)
|-------------------------------------------------------------------*/

namespace Tests\Unit;

class PriceCalculatorTest extends TestCase
{
    public function test_calculates_discount_correctly(): void
    {
        $calculator = new PriceCalculator();
        $result = $calculator->applyDiscount(100, 10);
        
        $this->assertEquals(90, $result);
        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }
    
    public function test_validates_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $calculator = new PriceCalculator();
        $calculator->applyDiscount(-100, 10);
    }
}

/* ------------------------------------------------------------------
|  SECTION 4: FEATURE TESTS (HTTP Endpoints)
|-------------------------------------------------------------------*/

namespace Tests\Feature;

class HomePageTest extends TestCase
{
    use RefreshDatabase;
    
    // Basic HTTP assertions
    public function test_homepage_loads(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertOk();
        $response->assertSee('Welcome');
        $response->assertViewIs('home');
        $response->assertViewHas('title', 'Home');
    }
    
    // POST request
    public function test_form_submission(): void
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello'
        ]);
        
        $response->assertRedirect('/thank-you');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('contacts', [
            'email' => 'john@example.com'
        ]);
    }
}

/* ------------------------------------------------------------------
|  SECTION 5: AUTHENTICATION TESTS (Common Interview Topic)
|-------------------------------------------------------------------*/

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    
    // Guest access
    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')
             ->assertRedirect('/login');
    }
    
    // Authenticated user
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
             ->get('/dashboard')
             ->assertOk()
             ->assertSee($user->name);
    }
    
    // With specific guard
    public function test_admin_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->actingAs($admin, 'admin')
             ->get('/admin/dashboard')
             ->assertOk();
    }
    
    // Login test
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt($password = 'password123')
        ]);
        
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password
        ]);
        
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }
    
    // Logout test
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
             ->post('/logout')
             ->assertRedirect('/');
        
        $this->assertGuest();
    }
}

/* ------------------------------------------------------------------
|  SECTION 6: API TESTING (RESTful Endpoints)
|-------------------------------------------------------------------*/

class TodoApiTest extends TestCase
{
    use RefreshDatabase;
    
    // JSON response assertions
    public function test_api_returns_todos_list(): void
    {
        Todo::factory()->count(3)->create();
        
        $response = $this->getJson('/api/todos');
        
        $response->assertOk()
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'title', 'completed']
                     ]
                 ]);
    }
    
    // Create resource
    public function test_api_creates_todo(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/todos', [
                             'title' => 'New Task'
                         ]);
        
        $response->assertCreated()
                 ->assertJsonFragment(['title' => 'New Task'])
                 ->assertJsonPath('data.title', 'New Task');
        
        $this->assertDatabaseHas('todos', [
            'title' => 'New Task',
            'user_id' => $user->id
        ]);
    }
    
    // Update resource
    public function test_api_updates_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user, 'api')
                         ->putJson("/api/todos/{$todo->id}", [
                             'title' => 'Updated Task',
                             'completed' => true
                         ]);
        
        $response->assertOk();
        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Updated Task',
            'completed' => true
        ]);
    }
    
    // Delete resource
    public function test_api_deletes_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user, 'api')
                         ->deleteJson("/api/todos/{$todo->id}");
        
        $response->assertNoContent();
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }
    
    // Validation errors
    public function test_api_validates_input(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/todos', []);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }
    
    // Unauthorized access
    public function test_api_requires_authentication(): void
    {
        $this->postJson('/api/todos', ['title' => 'Task'])
             ->assertUnauthorized();
    }
}

/* ------------------------------------------------------------------
|  SECTION 7: DATABASE TESTING (Critical for Interviews)
|-------------------------------------------------------------------*/

class DatabaseTest extends TestCase
{
    use RefreshDatabase;
    
    // Database assertions
    public function test_database_operations(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);
        
        // Assert record exists
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
        
        // Assert record count
        $this->assertDatabaseCount('users', 1);
        
        // Delete and assert missing
        $user->delete();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
        
        // Soft delete assertions
        $this->assertSoftDeleted('users', [
            'id' => $user->id
        ]);
    }
    
    // Model relationships
    public function test_model_relationships(): void
    {
        $user = User::factory()
                    ->has(Post::factory()->count(3))
                    ->create();
        
        $this->assertCount(3, $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts->first());
    }
}

/* ------------------------------------------------------------------
|  SECTION 8: FILE UPLOAD TESTING
|-------------------------------------------------------------------*/

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);
        
        $response = $this->actingAs($user)
                         ->post('/profile/avatar', [
                             'avatar' => $file
                         ]);
        
        $response->assertSessionHasNoErrors();
        
        // Assert file was stored
        Storage::disk('public')->assertExists('avatars/' . $file->hashName());
    }
    
    public function test_upload_validates_file_type(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf');
        
        $response = $this->actingAs($user)
                         ->post('/profile/avatar', [
                             'avatar' => $file
                         ]);
        
        $response->assertSessionHasErrors(['avatar']);
    }
    
    public function test_upload_validates_file_size(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        
        // Create 11MB file (exceeds 10MB limit)
        $file = UploadedFile::fake()->create('large.jpg', 11000);
        
        $response = $this->actingAs($user)
                         ->post('/profile/avatar', [
                             'avatar' => $file
                         ]);
        
        $response->assertSessionHasErrors(['avatar']);
    }
}

/* ------------------------------------------------------------------
|  SECTION 9: MAIL TESTING (Important Interview Topic)
|-------------------------------------------------------------------*/

use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class MailTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_welcome_email_is_sent(): void
    {
        Mail::fake();
        
        $user = User::factory()->create();
        
        // Trigger email
        event(new UserRegistered($user));
        
        // Assert email was sent
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->user->id === $user->id;
        });
    }
    
    public function test_email_is_not_sent_for_existing_users(): void
    {
        Mail::fake();
        
        // Code that shouldn't send email
        
        Mail::assertNothingSent();
    }
    
    public function test_email_is_queued(): void
    {
        Mail::fake();
        
        $user = User::factory()->create();
        Mail::to($user)->queue(new WelcomeEmail($user));
        
        Mail::assertQueued(WelcomeEmail::class);
    }
    
    public function test_email_content(): void
    {
        $user = User::factory()->create(['name' => 'John']);
        $mailable = new WelcomeEmail($user);
        
        $mailable->assertSeeInHtml('Welcome John');
        $mailable->assertSeeInText('Welcome John');
    }
}

/* ------------------------------------------------------------------
|  SECTION 10: QUEUE TESTING
|-------------------------------------------------------------------*/

use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessVideo;

class QueueTest extends TestCase
{
    public function test_job_is_dispatched(): void
    {
        Queue::fake();
        
        // Code that dispatches job
        ProcessVideo::dispatch($videoId = 123);
        
        // Assert job was pushed
        Queue::assertPushed(ProcessVideo::class, function ($job) use ($videoId) {
            return $job->videoId === $videoId;
        });
    }
    
    public function test_job_is_pushed_to_correct_queue(): void
    {
        Queue::fake();
        
        ProcessVideo::dispatch($videoId = 123);
        
        Queue::assertPushedOn('videos', ProcessVideo::class);
    }
    
    public function test_multiple_jobs_dispatched(): void
    {
        Queue::fake();
        
        ProcessVideo::dispatch(1);
        ProcessVideo::dispatch(2);
        ProcessVideo::dispatch(3);
        
        Queue::assertPushed(ProcessVideo::class, 3);
    }
}

/* ------------------------------------------------------------------
|  SECTION 11: EVENT TESTING
|-------------------------------------------------------------------*/

use Illuminate\Support\Facades\Event;
use App\Events\OrderPlaced;
use App\Listeners\SendOrderConfirmation;

class EventTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_event_is_dispatched(): void
    {
        Event::fake([OrderPlaced::class]);
        
        // Code that triggers event
        $order = Order::factory()->create();
        event(new OrderPlaced($order));
        
        Event::assertDispatched(OrderPlaced::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }
    
    public function test_listener_is_attached_to_event(): void
    {
        Event::fake();
        
        Event::assertListening(
            OrderPlaced::class,
            SendOrderConfirmation::class
        );
    }
}

/* ------------------------------------------------------------------
|  SECTION 12: NOTIFICATION TESTING
|-------------------------------------------------------------------*/

use Illuminate\Support\Facades\Notification;
use App\Notifications\InvoicePaid;

class NotificationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_notification_is_sent(): void
    {
        Notification::fake();
        
        $user = User::factory()->create();
        $user->notify(new InvoicePaid($invoice));
        
        Notification::assertSentTo($user, InvoicePaid::class);
    }
    
    public function test_notification_has_correct_channels(): void
    {
        Notification::fake();
        
        $user = User::factory()->create();
        $user->notify(new InvoicePaid($invoice));
        
        Notification::assertSentTo(
            $user,
            InvoicePaid::class,
            function ($notification, $channels) {
                return in_array('mail', $channels) &&
                       in_array('database', $channels);
            }
        );
    }
}

/* ------------------------------------------------------------------
|  SECTION 13: CACHE TESTING
|-------------------------------------------------------------------*/

use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    public function test_data_is_cached(): void
    {
        Cache::shouldReceive('get')
             ->once()
             ->with('key')
             ->andReturn('value');
        
        $result = Cache::get('key');
        
        $this->assertEquals('value', $result);
    }
    
    public function test_cache_forget(): void
    {
        Cache::put('key', 'value', 60);
        $this->assertTrue(Cache::has('key'));
        
        Cache::forget('key');
        $this->assertFalse(Cache::has('key'));
    }
}

/* ------------------------------------------------------------------
|  SECTION 14: SESSION TESTING
|-------------------------------------------------------------------*/

class SessionTest extends TestCase
{
    public function test_session_data(): void
    {
        $response = $this->withSession(['user_id' => 123])
                         ->get('/dashboard');
        
        $response->assertSessionHas('user_id', 123);
    }
    
    public function test_flash_messages(): void
    {
        $response = $this->post('/contact', $data);
        
        $response->assertSessionHas('success', 'Message sent!');
        $response->assertSessionHasNoErrors();
    }
}

/* ------------------------------------------------------------------
|  SECTION 15: VALIDATION TESTING
|-------------------------------------------------------------------*/

class ValidationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_required_fields(): void
    {
        $response = $this->post('/posts', []);
        
        $response->assertSessionHasErrors(['title', 'content']);
    }
    
    public function test_email_validation(): void
    {
        $response = $this->post('/users', [
            'email' => 'invalid-email'
        ]);
        
        $response->assertSessionHasErrors(['email']);
    }
    
    /** @dataProvider invalidDataProvider */
    public function test_validates_input($field, $value): void
    {
        $data = array_merge($this->validData(), [$field => $value]);
        
        $response = $this->post('/endpoint', $data);
        
        $response->assertSessionHasErrors([$field]);
    }
    
    public function invalidDataProvider(): array
    {
        return [
            'empty title' => ['title', ''],
            'invalid email' => ['email', 'not-an-email'],
            'short password' => ['password', '123'],
        ];
    }
}

/* ------------------------------------------------------------------
|  SECTION 16: MIDDLEWARE TESTING
|-------------------------------------------------------------------*/

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_admin_middleware_blocks_regular_users(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        $this->actingAs($user)
             ->get('/admin/dashboard')
             ->assertForbidden();
    }
    
    public function test_admin_middleware_allows_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->actingAs($admin)
             ->get('/admin/dashboard')
             ->assertOk();
    }
    
    public function test_throttle_middleware(): void
    {
        // Make 61 requests (assuming 60/min limit)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->get('/api/endpoint');
        }
        
        $response->assertStatus(429); // Too Many Requests
    }
}

/* ------------------------------------------------------------------
|  SECTION 17: CONSOLE COMMAND TESTING
|-------------------------------------------------------------------*/

class CommandTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_command_runs_successfully(): void
    {
        $this->artisan('app:cleanup-old-records')
             ->assertExitCode(0)
             ->expectsOutput('Cleanup completed!');
    }
    
    public function test_command_with_arguments(): void
    {
        $this->artisan('app:send-reminders', ['--days' => 7])
             ->assertExitCode(0);
    }
    
    public function test_command_with_confirmation(): void
    {
        $this->artisan('app:delete-users')
             ->expectsConfirmation('Are you sure?', 'yes')
             ->assertExitCode(0);
    }
}

/* ------------------------------------------------------------------
|  SECTION 18: MOCKING & SPIES (Advanced Interview Topic)
|-------------------------------------------------------------------*/

class MockingTest extends TestCase
{
    public function test_mocking_external_service(): void
    {
        $mock = $this->mock(PaymentGateway::class, function ($mock) {
            $mock->shouldReceive('charge')
                 ->once()
                 ->with(100)
                 ->andReturn(true);
        });
        
        $result = $mock->charge(100);
        
        $this->assertTrue($result);
    }
    
    public function test_partial_mock(): void
    {
        $mock = $this->partialMock(UserService::class, function ($mock) {
            $mock->shouldReceive('sendEmail')
                 ->once()
                 ->andReturn(true);
        });
        
        // Other methods work normally
        $user = $mock->createUser(['name' => 'John']);
        $this->assertInstanceOf(User::class, $user);
    }
    
    public function test_spy(): void
    {
        $spy = $this->spy(Logger::class);
        
        // Code that uses logger
        app(SomeService::class)->performAction();
        
        // Assert logger was called
        $spy->shouldHaveReceived('log')->once();
    }
}

/* ------------------------------------------------------------------
|  SECTION 19: CUSTOM ASSERTIONS & TEST HELPERS
|-------------------------------------------------------------------*/

trait TestHelpers
{
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }
    
    protected function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
    
    protected function signIn(?User $user = null): self
    {
        $user = $user ?: $this->createUser();
        $this->actingAs($user);
        
        return $this;
    }
    
    protected function signInAdmin(): self
    {
        $this->actingAs($this->createAdmin());
        
        return $this;
    }
    
    protected function jsonAs(User $user, string $method, string $uri, array $data = [])
    {
        return $this->actingAs($user, 'api')
                    ->json($method, $uri, $data);
    }
}

/* ------------------------------------------------------------------
|  SECTION 20: RUNNING TESTS (Interview Commands)
|-------------------------------------------------------------------*/

# Run all tests
php artisan test
./vendor/bin/phpunit

# Run specific test file
php artisan test tests/Feature/UserTest.php
php artisan test --filter=UserTest

# Run specific test method
php artisan test --filter=test_user_can_login

# Run tests in parallel (faster)
php artisan test --parallel

# Run with coverage
php artisan test --coverage
php artisan test --coverage-html coverage

# Run tests for specific group
php artisan test --group=api
# In test: /** @group api */

# Stop on first failure
php artisan test --stop-on-failure

# Verbose output
php artisan test -v

/* ------------------------------------------------------------------
|  SECTION 21: COMMON ASSERTIONS QUICK REFERENCE
|-------------------------------------------------------------------*/

// HTTP Status Assertions
$response->assertOk();              // 200
$response->assertCreated();         // 201
$response->assertNoContent();       // 204
$response->assertNotFound();        // 404
$response->assertForbidden();       // 403
$response->assertUnauthorized();    // 401
$response->assertStatus(500);       // Custom status

// Redirect Assertions
$response->assertRedirect('/home');
$response->assertRedirectToRoute('home');

// View Assertions
$response->assertViewIs('home');
$response->assertViewHas('key', 'value');
$response->assertViewHasAll(['key1', 'key2']);

// JSON Assertions
$response->assertJson(['key' => 'value']);
$response->assertJsonFragment(['key' => 'value']);
$response->assertJsonStructure(['data' => ['id', 'name']]);
$response->assertJsonPath('data.0.id', 1);
$response->assertJsonCount(5, 'data');

// Session Assertions
$response->assertSessionHas('key', 'value');
$response->assertSessionHasNoErrors();
$response->assertSessionHasErrors(['field']);

// Database Assertions
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['id' => 1]);
$this->assertDatabaseCount('users', 5);
$this->assertSoftDeleted('users', ['id' => 1]);

// Authentication Assertions
$this->assertAuthenticated();
$this->assertGuest();
$this->assertAuthenticatedAs($user);

// General PHPUnit Assertions
$this->assertTrue($condition);
$this->assertFalse($condition);
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);  // Strict comparison
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertEmpty($array);
$this->assertCount(3, $array);
$this->assertContains('value', $array);
$this->assertInstanceOf(User::class, $user);

/* ------------------------------------------------------------------
|  SECTION 22: TROUBLESHOOTING (Interview Scenarios)
|-------------------------------------------------------------------*/

// Problem: Tests passing locally but failing in CI
// Solution:
php artisan config:clear
php artisan cache:clear
composer dump-autoload

// Problem: "Class not found" errors
// Solution:
composer dump-autoload

// Problem: Database tables don't exist
// Solution:
php artisan migrate --env=testing
// Or add RefreshDatabase trait

// Problem: Tests interfering with each other
// Solution: Add RefreshDatabase or DatabaseTransactions trait

// Problem: File upload tests failing
// Solution: Use Storage::fake() at the start of the test

// Problem: Can't test authenticated routes
// Solution:
$user = User::factory()->create();
$this->actingAs($user)->get('/dashboard');

// Problem: Middleware blocking tests
// Solution: Use WithoutMiddleware trait or disable specific middleware:
$this->withoutMiddleware(ThrottleRequests::class);

// Problem: Time-based tests failing
// Solution: Use Carbon::setTestNow()
use Illuminate\Support\Carbon;
Carbon::setTestNow('2025-01-01 12:00:00');

/* ------------------------------------------------------------------
|  SECTION 23: BEST PRACTICES (Interview Discussion Points)
|-------------------------------------------------------------------*/

/*
1. Test Naming Convention:
   - Use descriptive names: test_user_can_create_post()
   - Or: it_creates_post_successfully()

2. AAA Pattern (Arrange, Act, Assert):
   public function test_example(): void
   {
       // Arrange: Set up test data
       $user = User::factory()->create();
       
       // Act: Perform the action
       $response = $this->actingAs($user)->post('/posts', $data);
       
       // Assert: Verify the outcome
       $response->assertCreated();
       $this->assertDatabaseHas('posts', $data);
   }

3. Use Factories Over Manual Creation:
   // Good
   $user = User::factory()->create();
   
   // Avoid
   $user = User::create(['name' => '...', 'email' => '...']);

4. Test One Thing Per Test:
   // Good: Separate tests
   test_user_can_register()
   test_user_receives_welcome_email()
   
   // Bad: Combined test
   test_user_registration_flow()

5. Use Descriptive Assertions:
   // Good
   $this->assertDatabaseHas('posts', ['title' => 'Test']);
   
   // Less clear
   $this->assertTrue(Post::where('title', 'Test')->exists());

6. Clean Up After Tests:
   - Use RefreshDatabase for database isolation
   - Use fake() methods for external services

7. Avoid Testing Framework Code:
   - Don't test Laravel's built-in features
   - Test your business logic

8. Keep Tests Fast:
   - Use in-memory SQLite database
   - Mock external API calls
   - Run tests in parallel when possible
*/

/* ------------------------------------------------------------------
|  SECTION 24: INTERVIEW QUESTIONS & ANSWERS
|-------------------------------------------------------------------*/

/*
Q: What's the difference between RefreshDatabase and DatabaseTransactions?

A: RefreshDatabase migrates the database before each test and rolls back 
   after. DatabaseTransactions wraps each test in a transaction and rolls 
   back. RefreshDatabase is slower but ensures clean slate. 
   DatabaseTransactions is faster but assumes migrations are already run.

Q: How do you test a protected or private method?

A: You shouldn't directly test private methods. Test the public interface 
   that uses them. If you must, use reflection:
   
   $method = new ReflectionMethod(MyClass::class, 'privateMethod');
   $method->setAccessible(true);
   $result = $method->invoke($object, $args);

Q: How do you test code that depends on the current time?

A: Use Carbon::setTestNow() to freeze time:
   
   Carbon::setTestNow('2025-01-01 12:00:00');
   // Your test code
   Carbon::setTestNow(); // Reset

Q: What's the difference between Mock and Spy?

A: Mock: Define expected behavior before the test runs
   Spy: Records actual calls during test, verify after
   
   Mock: Strict, fails if expectations not met
   Spy: Flexible, check what actually happened

Q: How do you test API rate limiting?

A: Make requests in a loop until you hit the limit:
   
   for ($i = 0; $i < 61; $i++) {
       $response = $this->get('/api/endpoint');
   }
   $response->assertStatus(429);

Q: How do you test file downloads?

A: Assert the response type and content:
   
   $response = $this->get('/download/file.pdf');
   $response->assertOk();
   $response->assertHeader('Content-Type', 'application/pdf');
   $response->assertDownload('file.pdf');

Q: Best practice for testing external APIs?

A: Always mock external services in tests:
   
   Http::fake([
       'api.example.com/*' => Http::response(['data' => 'value'], 200)
   ]);
*/

/* ------------------------------------------------------------------
|  FINAL CHECKLIST FOR INTERVIEWS
|-------------------------------------------------------------------*/

/*
✓ Know how to set up test environment (.env.testing)
✓ Understand difference between unit and feature tests
✓ Master authentication testing (actingAs)
✓ Know all HTTP assertion methods
✓ Understand database testing and factories
✓ Know how to fake external services (Mail, Queue, Storage, etc.)
✓ Understand mocking vs spying
```php
✓ Master JSON API testing patterns
✓ Know validation testing approaches
✓ Understand test lifecycle (setUp, tearDown)
✓ Can explain test isolation strategies
✓ Know how to run and filter tests
*/

/* ------------------------------------------------------------------
|  SECTION 25: ADVANCED TESTING PATTERNS
|-------------------------------------------------------------------*/

// Data Providers for Multiple Test Cases
class ProductTest extends TestCase
{
    /**
     * @dataProvider priceDataProvider
     */
    public function test_price_calculations($input, $discount, $expected): void
    {
        $result = $this->calculator->calculate($input, $discount);
        $this->assertEquals($expected, $result);
    }
    
    public function priceDataProvider(): array
    {
        return [
            'no discount' => [100, 0, 100],
            '10% discount' => [100, 10, 90],
            '50% discount' => [100, 50, 50],
            'full discount' => [100, 100, 0],
        ];
    }
}

// Test Dependencies
class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_create_order(): Order
    {
        $order = Order::factory()->create();
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        
        return $order;
    }
    
    /**
     * @depends test_create_order
     */
    public function test_process_payment(Order $order): void
    {
        $result = $this->paymentService->process($order);
        $this->assertTrue($result);
    }
}

// Custom Assertions
class CustomAssertionsTest extends TestCase
{
    protected function assertUserIsAdmin(User $user): void
    {
        $this->assertEquals('admin', $user->role, 'User is not an admin');
        $this->assertTrue($user->hasPermission('admin'), 'User lacks admin permissions');
    }
    
    protected function assertValidEmail(string $email): void
    {
        $this->assertMatchesRegularExpression(
            '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            $email,
            "'{$email}' is not a valid email address"
        );
    }
}

/* ------------------------------------------------------------------
|  SECTION 26: TESTING ELOQUENT MODELS
|-------------------------------------------------------------------*/

class UserModelTest extends TestCase
{
    use RefreshDatabase;
    
    // Test relationships
    public function test_user_has_many_posts(): void
    {
        $user = User::factory()
                    ->has(Post::factory()->count(3))
                    ->create();
        
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->posts);
        $this->assertCount(3, $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts->first());
    }
    
    // Test belongs to relationship
    public function test_post_belongs_to_user(): void
    {
        $post = Post::factory()->create();
        
        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($post->user_id, $post->user->id);
    }
    
    // Test many-to-many relationship
    public function test_user_has_many_roles(): void
    {
        $user = User::factory()->create();
        $roles = Role::factory()->count(2)->create();
        
        $user->roles()->attach($roles);
        
        $this->assertCount(2, $user->roles);
        $this->assertTrue($user->roles->contains($roles[0]));
    }
    
    // Test scopes
    public function test_active_scope_filters_users(): void
    {
        User::factory()->create(['active' => true]);
        User::factory()->create(['active' => false]);
        
        $activeUsers = User::active()->get();
        
        $this->assertCount(1, $activeUsers);
        $this->assertTrue($activeUsers->first()->active);
    }
    
    // Test accessors/mutators
    public function test_full_name_accessor(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
        
        $this->assertEquals('John Doe', $user->full_name);
    }
    
    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plain-text-password'
        ]);
        
        $this->assertNotEquals('plain-text-password', $user->password);
        $this->assertTrue(Hash::check('plain-text-password', $user->password));
    }
    
    // Test model events
    public function test_user_deleted_event_fires(): void
    {
        Event::fake();
        
        $user = User::factory()->create();
        $user->delete();
        
        Event::assertDispatched('eloquent.deleted: ' . User::class);
    }
    
    // Test soft deletes
    public function test_user_soft_deletes(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        
        $user->delete();
        
        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertDatabaseHas('users', ['id' => $userId]);
        $this->assertNotNull(User::withTrashed()->find($userId)->deleted_at);
    }
    
    // Test restore
    public function test_user_can_be_restored(): void
    {
        $user = User::factory()->create();
        $user->delete();
        $user->restore();
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null
        ]);
    }
    
    // Test force delete
    public function test_user_force_delete(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        
        $user->forceDelete();
        
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }
}

/* ------------------------------------------------------------------
|  SECTION 27: TESTING POLICIES & AUTHORIZATION
|-------------------------------------------------------------------*/

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_view_any_posts(): void
    {
        $user = User::factory()->create();
        
        $this->assertTrue($user->can('viewAny', Post::class));
    }
    
    public function test_user_can_view_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        $this->assertTrue($user->can('view', $post));
    }
    
    public function test_user_cannot_update_others_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);
        
        $this->assertFalse($user->can('update', $post));
    }
    
    public function test_admin_can_delete_any_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create();
        
        $this->assertTrue($admin->can('delete', $post));
    }
    
    public function test_policy_in_controller(): void
    {
        $user = User::factory()->create();
        $otherPost = Post::factory()->create();
        
        $response = $this->actingAs($user)
                         ->delete("/posts/{$otherPost->id}");
        
        $response->assertForbidden();
    }
}

/* ------------------------------------------------------------------
|  SECTION 28: TESTING FORM REQUESTS
|-------------------------------------------------------------------*/

class StorePostRequestTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_request_validates_required_fields(): void
    {
        $request = new StorePostRequest();
        $validator = Validator::make([], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
        $this->assertTrue($validator->errors()->has('content'));
    }
    
    public function test_request_validates_title_length(): void
    {
        $request = new StorePostRequest();
        $validator = Validator::make([
            'title' => str_repeat('a', 256),
            'content' => 'Valid content'
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }
    
    public function test_request_passes_with_valid_data(): void
    {
        $request = new StorePostRequest();
        $validator = Validator::make([
            'title' => 'Valid Title',
            'content' => 'Valid content here'
        ], $request->rules());
        
        $this->assertFalse($validator->fails());
    }
    
    public function test_authorization_check(): void
    {
        $user = User::factory()->create(['role' => 'guest']);
        $request = new StorePostRequest();
        $request->setUserResolver(fn() => $user);
        
        $this->assertFalse($request->authorize());
    }
}

/* ------------------------------------------------------------------
|  SECTION 29: TESTING JOBS
|-------------------------------------------------------------------*/

class ProcessVideoJobTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_job_processes_video(): void
    {
        Storage::fake('videos');
        $video = Video::factory()->create(['status' => 'pending']);
        
        $job = new ProcessVideo($video);
        $job->handle();
        
        $video->refresh();
        $this->assertEquals('processed', $video->status);
    }
    
    public function test_job_handles_failure(): void
    {
        $video = Video::factory()->create();
        $job = new ProcessVideo($video);
        
        // Simulate failure
        $exception = new \Exception('Processing failed');
        $job->failed($exception);
        
        $video->refresh();
        $this->assertEquals('failed', $video->status);
    }
    
    public function test_job_retry_logic(): void
    {
        $job = new ProcessVideo(Video::factory()->create());
        
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
    }
    
    public function test_job_dispatched_with_delay(): void
    {
        Queue::fake();
        
        ProcessVideo::dispatch($video)->delay(now()->addMinutes(10));
        
        Queue::assertPushed(ProcessVideo::class, function ($job) {
            return $job->delay !== null;
        });
    }
}

/* ------------------------------------------------------------------
|  SECTION 30: TESTING LISTENERS
|-------------------------------------------------------------------*/

class SendWelcomeEmailListenerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_listener_sends_email(): void
    {
        Mail::fake();
        
        $user = User::factory()->create();
        $event = new UserRegistered($user);
        $listener = new SendWelcomeEmail();
        
        $listener->handle($event);
        
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
    
    public function test_listener_handles_queue(): void
    {
        $listener = new SendWelcomeEmail();
        
        $this->assertTrue(method_exists($listener, 'shouldQueue'));
    }
}

/* ------------------------------------------------------------------
|  SECTION 31: TESTING BROADCASTING
|-------------------------------------------------------------------*/

use Illuminate\Support\Facades\Broadcast;

class BroadcastingTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_event_broadcasts_on_correct_channel(): void
    {
        Broadcast::shouldReceive('channel')
                 ->once()
                 ->with('order.123', Mockery::any());
        
        $order = Order::factory()->create(['id' => 123]);
        broadcast(new OrderShipped($order));
    }
    
    public function test_private_channel_authorization(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
             ->get('/broadcasting/auth', [
                 'channel_name' => 'private-user.' . $user->id
             ])
             ->assertOk();
    }
}

/* ------------------------------------------------------------------
|  SECTION 32: TESTING HTTP CLIENT (External APIs)
|-------------------------------------------------------------------*/

use Illuminate\Support\Facades\Http;

class ExternalApiTest extends TestCase
{
    public function test_fetches_user_data_from_api(): void
    {
        Http::fake([
            'api.example.com/users/*' => Http::response([
                'id' => 1,
                'name' => 'John Doe'
            ], 200)
        ]);
        
        $service = new UserApiService();
        $user = $service->getUser(1);
        
        $this->assertEquals('John Doe', $user['name']);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/users/1';
        });
    }
    
    public function test_handles_api_errors(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(null, 500)
        ]);
        
        $service = new UserApiService();
        
        $this->expectException(ApiException::class);
        $service->getUser(1);
    }
    
    public function test_api_timeout_handling(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException();
        });
        
        $service = new UserApiService();
        
        $this->expectException(\Illuminate\Http\Client\ConnectionException::class);
        $service->getUser(1);
    }
    
    public function test_api_with_authentication(): void
    {
        Http::fake();
        
        $service = new UserApiService();
        $service->withToken('secret-token')->getUser(1);
        
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer secret-token');
        });
    }
}

/* ------------------------------------------------------------------
|  SECTION 33: TESTING PAGINATION
|-------------------------------------------------------------------*/

class PaginationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_paginated_results(): void
    {
        Post::factory()->count(25)->create();
        
        $response = $this->getJson('/api/posts?page=1');
        
        $response->assertOk()
                 ->assertJsonCount(15, 'data')
                 ->assertJsonStructure([
                     'data',
                     'links' => ['first', 'last', 'prev', 'next'],
                     'meta' => ['current_page', 'total', 'per_page']
                 ]);
    }
    
    public function test_last_page_has_fewer_items(): void
    {
        Post::factory()->count(25)->create();
        
        $response = $this->getJson('/api/posts?page=2');
        
        $response->assertOk()
                 ->assertJsonCount(10, 'data');
    }
}

/* ------------------------------------------------------------------
|  SECTION 34: TESTING SEARCH FUNCTIONALITY
|-------------------------------------------------------------------*/

class SearchTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_search_finds_matching_posts(): void
    {
        Post::factory()->create(['title' => 'Laravel Testing Guide']);
        Post::factory()->create(['title' => 'PHP Best Practices']);
        Post::factory()->create(['title' => 'Laravel Authentication']);
        
        $response = $this->get('/posts?search=Laravel');
        
        $response->assertOk()
                 ->assertSee('Laravel Testing Guide')
                 ->assertSee('Laravel Authentication')
                 ->assertDontSee('PHP Best Practices');
    }
    
    public function test_search_returns_empty_for_no_matches(): void
    {
        Post::factory()->count(3)->create();
        
        $response = $this->get('/posts?search=NonExistent');
        
        $response->assertOk()
                 ->assertSee('No results found');
    }
}

/* ------------------------------------------------------------------
|  SECTION 35: TESTING FILTERS & SORTING
|-------------------------------------------------------------------*/

class FilterSortTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_filters_by_status(): void
    {
        Post::factory()->create(['status' => 'published']);
        Post::factory()->create(['status' => 'draft']);
        
        $response = $this->getJson('/api/posts?status=published');
        
        $response->assertOk()
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['status' => 'published']);
    }
    
    public function test_sorts_by_created_date(): void
    {
        $old = Post::factory()->create(['created_at' => now()->subDays(5)]);
        $new = Post::factory()->create(['created_at' => now()]);
        
        $response = $this->getJson('/api/posts?sort=created_at&order=desc');
        
        $data = $response->json('data');
        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($old->id, $data[1]['id']);
    }
    
    public function test_multiple_filters(): void
    {
        Post::factory()->create(['status' => 'published', 'featured' => true]);
        Post::factory()->create(['status' => 'published', 'featured' => false]);
        Post::factory()->create(['status' => 'draft', 'featured' => true]);
        
        $response = $this->getJson('/api/posts?status=published&featured=1');
        
        $response->assertOk()
                 ->assertJsonCount(1, 'data');
    }
}

/* ------------------------------------------------------------------
|  SECTION 36: TESTING RATE LIMITING & THROTTLING
|-------------------------------------------------------------------*/

class RateLimitTest extends TestCase
{
    public function test_rate_limit_allows_requests(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/api/endpoint');
            $response->assertOk();
        }
    }
    
    public function test_rate_limit_blocks_excess_requests(): void
    {
        // Assuming 60 requests per minute limit
        for ($i = 0; $i < 60; $i++) {
            $this->get('/api/endpoint');
        }
        
        $response = $this->get('/api/endpoint');
        $response->assertStatus(429);
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }
    
    public function test_rate_limit_per_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // User 1 maxes out their limit
        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user1)->get('/api/endpoint');
        }
        
        // User 2 should still be able to make requests
        $response = $this->actingAs($user2)->get('/api/endpoint');
        $response->assertOk();
    }
}

/* ------------------------------------------------------------------
|  SECTION 37: TESTING LOCALIZATION (i18n)
|-------------------------------------------------------------------*/

class LocalizationTest extends TestCase
{
    public function test_returns_english_translation(): void
    {
        App::setLocale('en');
        
        $response = $this->get('/');
        
        $response->assertSee('Welcome');
    }
    
    public function test_returns_spanish_translation(): void
    {
        App::setLocale('es');
        
        $response = $this->get('/');
        
        $response->assertSee('Bienvenido');
    }
    
    public function test_translation_with_parameters(): void
    {
        $translation = __('messages.greeting', ['name' => 'John']);
        
        $this->assertEquals('Hello, John!', $translation);
    }
    
    public function test_fallback_locale(): void
    {
        App::setLocale('fr');
        App::setFallbackLocale('en');
        
        // If French translation missing, falls back to English
        $translation = __('messages.welcome');
        
        $this->assertNotEmpty($translation);
    }
}

/* ------------------------------------------------------------------
|  SECTION 38: TESTING SCHEDULED TASKS
|-------------------------------------------------------------------*/

class ScheduledTaskTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_scheduled_command_runs(): void
    {
        $this->artisan('app:send-daily-report')
             ->assertExitCode(0);
        
        Mail::assertSent(DailyReport::class);
    }
    
    public function test_schedule_frequency(): void
    {
        $schedule = app()->make(Schedule::class);
        
        $events = collect($schedule->events())
            ->filter(function ($event) {
                return str_contains($event->command, 'app:send-daily-report');
            });
        
        $this->assertCount(1, $events);
        $this->assertTrue($events->first()->isDue(app()));
    }
}

/* ------------------------------------------------------------------
|  SECTION 39: TESTING WEBSOCKETS (Laravel Echo/Pusher)
|-------------------------------------------------------------------*/

class WebSocketTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_message_broadcasted_to_channel(): void
    {
        Event::fake();
        
        $user = User::factory()->create();
        $message = Message::factory()->create(['user_id' => $user->id]);
        
        broadcast(new MessageSent($message));
        
        Event::assertDispatched(MessageSent::class);
    }
    
    public function test_user_can_join_private_channel(): void
    {
        $user = User::factory()->create();
        
        Broadcast::channel('chat.{userId}', function ($user, $userId) {
            return (int) $user->id === (int) $userId;
        });
        
        $this->actingAs($user);
        
        $this->assertTrue(
            Broadcast::check('chat.' . $user->id, $user)
        );
    }
}

/* ------------------------------------------------------------------
|  SECTION 40: TESTING BLADE COMPONENTS
|-------------------------------------------------------------------*/

class BladeComponentTest extends TestCase
{
    public function test_alert_component_renders(): void
    {
        $view = $this->blade(
            '<x-alert type="success">Operation successful</x-alert>'
        );
        
        $view->assertSee('Operation successful');
        $view->assertSee('success');
    }
    
    public function test_component_with_slots(): void
    {
        $view = $this->blade(
            '<x-card>
                <x-slot name="title">Card Title</x-slot>
                Card content here
            </x-card>'
        );
        
        $view->assertSee('Card Title');
        $view->assertSee('Card content here');
    }
    
    public function test_component_class_logic(): void
    {
        $component = new AlertComponent('danger');
        
        $this->assertEquals('bg-red-500', $component->backgroundColor());
    }
}

/* ------------------------------------------------------------------
|  SECTION 41: TESTING VIEW COMPOSERS
|-------------------------------------------------------------------*/

class ViewComposerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_composer_adds_data_to_view(): void
    {
        User::factory()->count(5)->create();
        
        $view = view('dashboard');
        
        $this->assertArrayHasKey('users', $view->getData());
        $this->assertCount(5, $view->getData()['users']);
    }
}

/* ------------------------------------------------------------------
|  SECTION 42: TESTING COOKIES
|-------------------------------------------------------------------*/

class CookieTest extends TestCase
{
    public function test_cookie_is_set(): void
    {
        $response = $this->get('/set-preferences');
        
        $response->assertCookie('theme', 'dark');
        $response->assertCookie('language', 'en');
    }
    
    public function test_cookie_is_encrypted(): void
    {
        $response = $this->get('/set-secure-cookie');
        
        $response->assertCookie('secure_token');
        
        // Verify cookie is encrypted (not plain text)
        $cookie = $response->getCookie('secure_token');
        $this->assertNotEquals('plain-value', $cookie->getValue());
    }
    
    public function test_request_with_cookie(): void
    {
        $response = $this->withCookie('user_id', '123')
                         ->get('/dashboard');
        
        $response->assertOk();
    }
}

/* ------------------------------------------------------------------
|  SECTION 43: TESTING HEADERS
|-------------------------------------------------------------------*/

class HeaderTest extends TestCase
{
    public function test_cors_headers_present(): void
    {
        $response = $this->get('/api/endpoint');
        
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }
    
    public function test_custom_header(): void
    {
        $response = $this->get('/');
        
        $response->assertHeader('X-Custom-Header', 'value');
    }
    
    public function test_request_with_custom_header(): void
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'secret-key'
        ])->get('/api/protected');
        
        $response->assertOk();
    }
}

/* ------------------------------------------------------------------
|  SECTION 44: TESTING EXCEPTIONS & ERROR HANDLING
|-------------------------------------------------------------------*/

class ExceptionTest extends TestCase
{
    public function test_404_page_renders(): void
    {
        $response = $this->get('/non-existent-page');
        
        $response->assertNotFound();
        $response->assertSee('Page Not Found');
    }
    
    public function test_custom_exception_handler(): void
    {
        $this->withoutExceptionHandling();
        
        $this->expectException(CustomException::class);
        
        $this->get('/trigger-exception');
    }
    
    public function test_validation_exception_format(): void
    {
        $response = $this->postJson('/api/users', []);
        
        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'message',
                     'errors' => ['email', 'password']
                 ]);
    }
    
    public function test_exception_logged(): void
    {
        Log::shouldReceive('error')
           ->once()
           ->with(Mockery::type('string'), Mockery::type('array'));
        
        $this->get('/endpoint-that-throws');
    }
}

/* ------------------------------------------------------------------
|  SECTION 45: PERFORMANCE TESTING
|-------------------------------------------------------------------*/

class PerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_query_count(): void
    {
        DB::enableQueryLog();
        
        $users = User::with('posts')->limit(10)->get();
        
        $queries = DB::getQueryLog();
        
        // Should be 2 queries (1 for users, 1 for posts)
        $this->assertCount(2, $queries);
    }
    
    public function test_n_plus_one_problem_avoided(): void
    {
        User::factory()
            ->has(Post::factory()->count(5))
            ->count(10)
            ->create();
        
        DB::enableQueryLog();
        
        // Bad: N+1 problem
        // $users = User::all();
        // foreach ($users as $user) {
        //     $user->posts;
        // }
        
        // Good: Eager loading
        $users = User::with('posts')->get();
        
        $queries = DB::getQueryLog();
        
        // Should only be 2 queries regardless of user count
        $this->assertLessThanOrEqual(2, count($queries));
    }
    
    public function test_response_time(): void
    {
        $start = microtime(true);
        
        $this->get('/');
        
        $duration = microtime(true) - $start;
        
        // Assert response under 1 second
        $this->assertLessThan(1.0, $duration);
    }
}

/* ------------------------------------------------------------------
|  SECTION 46: TESTING FACTORIES & SEEDERS
|-------------------------------------------------------------------*/

class FactoryTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_factory_creates_user(): void
    {
        $user = User::factory()->create();
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email
        ]);
    }
    
    public function test_factory_with_custom_attributes(): void
    {
        $user = User::factory()->create([
            'email' => 'custom@example.com',
            'role' => 'admin'
        ]);
        
        $this->assertEquals('custom@example.com', $user->email);
        $this->assertEquals('admin', $user->role);
    }
    
    public function test_factory_states(): void
    {
        $admin = User::factory()->admin()->create();
        $suspended = User::factory()->suspended()->create();
        
        $this->assertEquals('admin', $admin->role);
        $this->assertTrue($suspended->is_suspended);
    }
    
    public function test_seeder_populates_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        
        $this->assertDatabaseCount('users', 10);
        $this->assertDatabaseCount('posts', 50);
    }
}

/* ------------------------------------------------------------------
|  SECTION 47: INTEGRATION TESTING (Full Stack)
|-------------------------------------------------------------------*/

class FullUserJourneyTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_complete_user_registration_flow(): void
    {
        Mail::fake();
        
        // Visit registration page
        $response = $this->get('/register');
        $response->assertOk();
        
        // Submit registration
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        
        $response->assertRedirect('/dashboard');
        
        // Verify user created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);
        
        // Verify welcome email sent
        Mail::assertSent(WelcomeEmail::class);
        
        // User is authenticated
        $this->assertAuthenticated();
        
        // Can access dashboard
        $response = $this->get('/dashboard');
        $response->assertOk();
    }
    
    public function test_complete_checkout_process(): void
    {
        Queue::fake();
        Mail::fake();
        
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 99.99]);
        
        // Add to cart
        $this->actingAs($user)
             ->post('/cart/make it pass) → Refactor (improve code)
   
   □ "How do you test error handling?"
      → Use expectException(), test validation errors, test API error responses
   
   □ "What's the difference between integration and E2E tests?"
      → Integration: Multiple units working together
      → E2E: Complete user journey through UI
   
   □ "How do you speed up test execution?"
      → Use in-memory DB, parallel testing, mock external services, lazy loading
*/

/* ------------------------------------------------------------------
|  SECTION 71: REAL-WORLD TESTING SCENARIOS
|-------------------------------------------------------------------*/

// Scenario 1: E-commerce Order Processing
class OrderProcessingTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_complete_order_workflow(): void
    {
        Queue::fake();
        Mail::fake();
        Event::fake();
        
        // Arrange
        $user = User::factory()->create(['balance' => 1000]);
        $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
        
        // Act: Create order
        $response = $this->actingAs($user)
                         ->postJson('/api/orders', [
                             'product_id' => $product->id,
                             'quantity' => 2
                         ]);
        
        // Assert: Order created
        $response->assertCreated();
        $order = Order::first();
        $this->assertEquals(200, $order->total);
        
        // Assert: Stock decreased
        $product->refresh();
        $this->assertEquals(8, $product->stock);
        
        // Assert: User balance decreased
        $user->refresh();
        $this->assertEquals(800, $user->balance);
        
        // Assert: Events dispatched
        Event::assertDispatched(OrderPlaced::class);
        
        // Assert: Jobs queued
        Queue::assertPushed(ProcessPayment::class);
        Queue::assertPushed(SendOrderConfirmation::class);
        
        // Assert: Email sent
        Mail::assertQueued(OrderConfirmationMail::class);
    }
    
    public function test_order_fails_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 1]);
        
        $response = $this->actingAs($user)
                         ->postJson('/api/orders', [
                             'product_id' => $product->id,
                             'quantity' => 5
                         ]);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['quantity']);
        
        $this->assertEquals(0, Order::count());
    }
    
    public function test_order_fails_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 50]);
        $product = Product::factory()->create(['price' => 100]);
        
        $response = $this->actingAs($user)
                         ->postJson('/api/orders', [
                             'product_id' => $product->id,
                             'quantity' => 1
                         ]);
        
        $response->assertStatus(402); // Payment Required
        $this->assertEquals(0, Order::count());
    }
}

// Scenario 2: Social Media Post with Comments
class SocialMediaTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_creates_post_with_mentions(): void
    {
        Notification::fake();
        
        $author = User::factory()->create();
        $mentioned = User::factory()->create(['username' => 'johndoe']);
        
        $response = $this->actingAs($author)
                         ->postJson('/api/posts', [
                             'content' => 'Hello @johndoe, check this out!'
                         ]);
        
        $response->assertCreated();
        
        // Verify post created
        $post = Post::first();
        $this->assertEquals($author->id, $post->user_id);
        
        // Verify mention notification sent
        Notification::assertSentTo(
            $mentioned,
            MentionNotification::class
        );
    }
    
    public function test_nested_comments_structure(): void
    {
        $post = Post::factory()->create();
        $parentComment = Comment::factory()
                                ->for($post)
                                ->create();
        
        $childComment = Comment::factory()
                               ->for($post)
                               ->create(['parent_id' => $parentComment->id]);
        
        $this->assertEquals(1, $parentComment->replies->count());
        $this->assertEquals($parentComment->id, $childComment->parent_id);
    }
    
    public function test_like_unlike_functionality(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        // Like post
        $this->actingAs($user)
             ->postJson("/api/posts/{$post->id}/like")
             ->assertOk();
        
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
            'likeable_type' => Post::class
        ]);
        
        // Unlike post
        $this->actingAs($user)
             ->deleteJson("/api/posts/{$post->id}/like")
             ->assertOk();
        
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id
        ]);
    }
}

// Scenario 3: Multi-tenant Application
class MultiTenantTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_tenant_data_isolation(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $user1 = User::factory()->for($tenant1)->create();
        $user2 = User::factory()->for($tenant2)->create();
        
        Post::factory()->for($user1)->create(['title' => 'Tenant 1 Post']);
        Post::factory()->for($user2)->create(['title' => 'Tenant 2 Post']);
        
        // User 1 should only see their tenant's posts
        $response = $this->actingAs($user1)
                         ->getJson('/api/posts');
        
        $response->assertOk()
                 ->assertJsonFragment(['title' => 'Tenant 1 Post'])
                 ->assertJsonMissing(['title' => 'Tenant 2 Post']);
    }
    
    public function test_cross_tenant_access_denied(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $user1 = User::factory()->for($tenant1)->create();
        $post2 = Post::factory()
                     ->for(User::factory()->for($tenant2))
                     ->create();
        
        $response = $this->actingAs($user1)
                         ->getJson("/api/posts/{$post2->id}");
        
        $response->assertNotFound();
    }
}

// Scenario 4: Subscription & Billing System
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_subscribes_to_plan(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 9.99, 'interval' => 'monthly']);
        
        $response = $this->actingAs($user)
                         ->postJson('/api/subscriptions', [
                             'plan_id' => $plan->id,
                             'payment_method' => 'card_xxx'
                         ]);
        
        $response->assertCreated();
        
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
        
        $this->assertNotNull($user->fresh()->subscribed_until);
    }
    
    public function test_subscription_renewal(): void
    {
        Carbon::setTestNow('2025-01-01');
        
        $subscription = Subscription::factory()->create([
            'ends_at' => now()->addMonth(),
            'status' => 'active'
        ]);
        
        // Travel to renewal date
        $this->travel(32)->days();
        
        $this->artisan('subscriptions:renew');
        
        $subscription->refresh();
        $this->assertTrue($subscription->ends_at->isFuture());
    }
    
    public function test_subscription_cancellation(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()
                                   ->for($user)
                                   ->create(['status' => 'active']);
        
        $response = $this->actingAs($user)
                         ->deleteJson("/api/subscriptions/{$subscription->id}");
        
        $response->assertOk();
        
        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertNotNull($subscription->cancelled_at);
    }
    
    public function test_proration_on_plan_upgrade(): void
    {
        $user = User::factory()->create();
        $basicPlan = Plan::factory()->create(['price' => 9.99]);
        $premiumPlan = Plan::factory()->create(['price' => 19.99]);
        
        $subscription = Subscription::factory()
                                   ->for($user)
                                   ->for($basicPlan, 'plan')
                                   ->create(['created_at' => now()->subDays(15)]);
        
        $response = $this->actingAs($user)
                         ->putJson("/api/subscriptions/{$subscription->id}", [
                             'plan_id' => $premiumPlan->id
                         ]);
        
        $response->assertOk();
        
        // Check proration credit applied
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'type' => 'proration_credit'
        ]);
    }
}

// Scenario 5: Real-time Chat Application
class ChatTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_send_message_to_chat_room(): void
    {
        Event::fake();
        
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $room->users()->attach($user);
        
        $response = $this->actingAs($user)
                         ->postJson("/api/rooms/{$room->id}/messages", [
                             'content' => 'Hello everyone!'
                         ]);
        
        $response->assertCreated();
        
        $this->assertDatabaseHas('messages', [
            'chat_room_id' => $room->id,
            'user_id' => $user->id,
            'content' => 'Hello everyone!'
        ]);
        
        Event::assertDispatched(MessageSent::class);
    }
    
    public function test_user_typing_indicator(): void
    {
        Broadcast::shouldReceive('event')
                 ->once()
                 ->with(Mockery::type(UserTyping::class));
        
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        
        $this->actingAs($user)
             ->postJson("/api/rooms/{$room->id}/typing")
             ->assertOk();
    }
    
    public function test_unread_message_count(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $room->users()->attach($user, ['last_read_at' => now()->subHour()]);
        
        Message::factory()
               ->for($room)
               ->count(5)
               ->create(['created_at' => now()]);
        
        $response = $this->actingAs($user)
                         ->getJson('/api/rooms');
        
        $response->assertOk()
                 ->assertJsonPath('data.0.unread_count', 5);
    }
    
    public function test_mark_messages_as_read(): void
    {
        $user = User::factory()->create();
        $room = ChatRoom::factory()->create();
        $room->users()->attach($user);
        
        Message::factory()->for($room)->count(3)->create();
        
        $this->actingAs($user)
             ->postJson("/api/rooms/{$room->id}/read")
             ->assertOk();
        
        $pivot = $room->users()->where('user_id', $user->id)->first()->pivot;
        $this->assertNotNull($pivot->last_read_at);
    }
}

/* ------------------------------------------------------------------
|  SECTION 72: EDGE CASES & BOUNDARY TESTING
|-------------------------------------------------------------------*/

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;
    
    // Test with null values
    public function test_handles_null_input(): void
    {
        $response = $this->postJson('/api/posts', [
            'title' => null,
            'content' => 'Some content'
        ]);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }
    
    // Test with empty strings
    public function test_handles_empty_strings(): void
    {
        $response = $this->postJson('/api/posts', [
            'title' => '',
            'content' => ''
        ]);
        
        $response->assertStatus(422);
    }
    
    // Test with very long strings
    public function test_handles_max_length(): void
    {
        $longString = str_repeat('a', 256);
        
        $response = $this->postJson('/api/posts', [
            'title' => $longString,
            'content' => 'Content'
        ]);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }
    
    // Test with special characters
    public function test_handles_special_characters(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                         ->postJson('/api/posts', [
                             'title' => '<script>alert("XSS")</script>',
                             'content' => 'Content with émojis 🚀'
                         ]);
        
        $response->assertCreated();
        
        $post = Post::first();
        // Verify XSS protection
        $this->assertStringNotContainsString('<script>', $post->title);
    }
    
    // Test with boundary values
    public function test_age_boundary_values(): void
    {
        $validator = Validator::make(['age' => 17], ['age' => 'min:18']);
        $this->assertTrue($validator->fails());
        
        $validator = Validator::make(['age' => 18], ['age' => 'min:18']);
        $this->assertFalse($validator->fails());
        
        $validator = Validator::make(['age' => 19], ['age' => 'min:18']);
        $this->assertFalse($validator->fails());
    }
    
    // Test with large datasets
    public function test_pagination_with_large_dataset(): void
    {
        Post::factory()->count(1000)->create();
        
        $response = $this->getJson('/api/posts?per_page=100');
        
        $response->assertOk()
                 ->assertJsonCount(100, 'data');
    }
    
    // Test concurrent requests
    public function test_concurrent_order_creation(): void
    {
        $product = Product::factory()->create(['stock' => 1]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Simulate race condition
        DB::transaction(function () use ($product, $user1) {
            $this->actingAs($user1)
                 ->postJson('/api/orders', ['product_id' => $product->id]);
        });
        
        $response = $this->actingAs($user2)
                         ->postJson('/api/orders', ['product_id' => $product->id]);
        
        $response->assertStatus(422);
        $this->assertEquals(1, Order::count());
    }
    
    // Test with Unicode characters
    public function test_unicode_support(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                         ->postJson('/api/posts', [
                             'title' => '你好世界', // Chinese
                             'content' => 'مرحبا بالعالم' // Arabic
                         ]);
        
        $response->assertCreated();
        
        $post = Post::first();
        $this->assertEquals('你好世界', $post->title);
    }
    
    // Test timezone handling
    public function test_timezone_consistency(): void
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);
        
        Carbon::setTestNow('2025-01-01 12:00:00', 'UTC');
        
        $post = Post::factory()->for($user)->create();
        
        $this->assertEquals(
            '2025-01-01 12:00:00',
            $post->created_at->setTimezone('UTC')->format('Y-m-d H:i:s')
        );
    }
}

/* ------------------------------------------------------------------
|  SECTION 73: SECURITY TESTING
|-------------------------------------------------------------------*/

class SecurityTest extends TestCase
{
    use RefreshDatabase;
    
    // Test CSRF protection
    public function test_csrf_protection(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
    }
    
    // Test SQL injection prevention
    public function test_sql_injection_prevention(): void
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        $response = $this->getJson("/api/users?search={$maliciousInput}");
        
        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => 1]); // Table still exists
    }
    
    // Test mass assignment protection
    public function test_mass_assignment_protection(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($user)
                         ->putJson("/api/users/{$user->id}", [
                             'name' => 'New Name',
                             'role' => 'admin' // Attempt to escalate privileges
                         ]);
        
        $user->refresh();
        $this->assertEquals('user', $user->role); // Role unchanged
    }
    
    // Test authorization checks
    public function test_unauthorized_access_prevention(): void
    {
        $user = User::factory()->create();
        $otherUserPost = Post::factory()->create();
        
        $response = $this->actingAs($user)
                         ->deleteJson("/api/posts/{$otherUserPost->id}");
        
        $response->assertForbidden();
    }
    
    // Test rate limiting
    public function test_rate_limiting_on_auth_endpoints(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password'
            ]);
        }
        
        $response->assertStatus(429); // Too many requests
    }
    
    // Test password requirements
    public function test_weak_password_rejected(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123', // Weak password
            'password_confirmation' => '123'
        ]);
        
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }
    
    // Test session fixation prevention
    public function test_session_regeneration_on_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password')
        ]);
        
        $oldSessionId = session()->getId();
        
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);
        
        $newSessionId = session()->getId();
        
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }
    
    // Test sensitive data exposure
    public function test_passwords_not_exposed_in_api(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
                         ->getJson('/api/user');
        
        $response->assertOk()
                 ->assertJsonMissing(['password']);
    }
}

/* ------------------------------------------------------------------
|  SECTION 74: ACCESSIBILITY TESTING
|-------------------------------------------------------------------*/

class AccessibilityTest extends TestCase
{
    public function test_semantic_html_structure(): void
    {
        $response = $this->get('/');
        
        $response->assertSee('<nav', false);
        $response->assertSee('<main', false);
        $response->assertSee('<footer', false);
    }
    
    public function test_form_labels_present(): void
    {
        $response = $this->get('/contact');
        
        $response->assertSee('<label for="email"', false);
        $response->assertSee('<label for="message"', false);
    }
    
    public function test_images_have_alt_text(): void
    {
        $response = $this->get('/');
        
        // Check that images have alt attributes
        $this->assertStringNotContainsString('<img src="', $response->getContent());
        // Or use regex to validate all img tags have alt
    }
}

/* ------------------------------------------------------------------
|  SECTION 75: FINAL TIPS & BEST PRACTICES SUMMARY
|-------------------------------------------------------------------*/

/*
🎯 GOLDEN RULES OF TESTING

1. **Test Behavior, Not Implementation**
   ✅ Test what the code does
   ❌ Don't test how it does it

2. **Keep Tests Simple and Focused**
   ✅ One logical assertion per test
   ❌ Don't test multiple scenarios in one test

3. **Use Descriptive Test Names**
   ✅ test_user_cannot_delete_other_users_posts()
   ❌ test1()

4. **Follow AAA Pattern**
   - Arrange: Set up test data
   - Act: Execute the code
   - Assert: Verify the outcome

5. **Make Tests Independent**
   ✅ Each test should run in isolation
   ❌ Tests should not depend on each other

6. **Use Factories for Test Data**
   ✅ User::factory()->create()
   ❌ Manual User::create() with all fields

7. **Mock External Dependencies**
   ✅ Http::fake(), Mail::fake()
   ❌ Making real API calls in tests

8. **Test Edge Cases**
   - Null values
   - Empty arrays
   - Boundary values
   - Maximum/minimum limits

9. **Write Tests First (TDD)**
   - Red: Write failing test
   - Green: Make it pass
   - Refactor: Improve code

10. **Maintain Your Tests**
    - Update tests when requirements change
    - Remove obsolete tests
    - Refactor duplicate code

🚀 PERFORMANCE TIPS
- Use in-memory SQLite database
- Run tests in parallel
- Mock slow operations
- Use appropriate test doubles
- Avoid unnecessary database operations

📊 COVERAGE GOALS
- Aim for 80-90% code coverage
- 100% coverage on critical paths
- Don't chase 100% blindly
- Focus on meaningful tests

🔧 DEBUGGING TECHNIQUES
- Use $this->dump() and $this->dd()
- Enable query logging
- Use Ray or Telescope
- Run single test with --filter
- Use --stop-on-failure flag

💡 INTERVIEW TIPS
- Explain your testing philosophy
- Discuss trade-offs (speed vs thoroughness)
- Show knowledge of testing pyramid
- Mention CI/CD integration
- Talk about maintaining test suites
- Demonstrate TDD workflow
- Discuss real-world testing challenges

Remember: Good tests are:
✓ Fast
✓ Independent
✓ Repeatable
✓ Self-validating
✓ Timely (written at the right time)
*/

/*
|===================================================================================
| 🎓 CONGRATULATIONS!
|===================================================================================
| You now have a comprehensive Laravel testing cheat sheet covering:
| 
| ✅ Environment setup and configuration
| ✅ Unit, Feature, and Integration testing
| ✅ Database testing with factories and seeders
| ✅ API testing (REST, GraphQL)
| ✅ Authentication and authorization testing
| ✅ File uploads, emails, queues, events
| ✅ Mocking and test doubles
| ✅ Browser testing with Dusk
| ✅ Livewire and Inertia.js testing
| ✅ Real-world scenarios (e-commerce, social media, chat, subscriptions)
| ✅ Edge cases and security testing
| ✅ Performance optimization
| ✅ CI/CD integration
| ✅ Best practices and common pitfalls
|
| 🚀 You're now ready to ace any Laravel testing interview!
|===================================================================================
*/

?>
