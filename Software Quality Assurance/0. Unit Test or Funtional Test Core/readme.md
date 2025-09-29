Copy-paste the snippet below into a fresh file `tests/CheatSheet.php` (or just keep it in a Gist) and you will have a **single-page, copy-paste-ready “note”** that lets you **test ANY Laravel project** without reading the whole documentation again.

-------------------------------------------------
tests/CheatSheet.php
-------------------------------------------------
<?php
/*
|==================================================================================
| LARAVEL TESTING – MINIMAL SURVIVAL GUIDE
|==================================================================================
| 1.  Add this file to project (tests/CheatSheet.php)
| 2.  `composer require --dev phpunit/phpunit` (already shipped with Laravel)
| 3.  `php artisan test` or `./vendor/bin/phpunit --filter=name`
|
| Everything below is **framework-agnostic** – works for any Laravel app.
|==================================================================================
*/

/* ------------------------------------------------------------------
|  0.  ONE-TIME ENVIRONMENT SET-UP
|-------------------------------------------------------------------*/
# .env.testing   (copy from .env)
APP_ENV=testing
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
DB_CONNECTION=sqlite
DB_DATABASE=:memory:          # fastest – fresh DB every test
# DB_DATABASE=db_test.sqlite  # or real file if you want to inspect

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
FILESYSTEM_DISK=testing       # so Storage::fake() works out of the box

# After creating the file:
#    php artisan config:clear
#    php artisan migrate --env=testing

/* ------------------------------------------------------------------
|  1.  BASE CLASS EVERY TEST EXTENDS
|-------------------------------------------------------------------*/
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;   // boots Laravel once
}

/* ------------------------------------------------------------------
|  2.  TRAITS YOU CAN COMBINE
|-------------------------------------------------------------------*/
RefreshDatabase      # runs migrations in memory and rolls back after each test
DatabaseTransactions # keeps real DB, wraps every test in transaction
WithFaker            # gives you $this->faker
WithoutMiddleware    # disables all middlware (auth, throttle, etc.)
WithoutEvents        # no Model/observer side effects

/* ------------------------------------------------------------------
|  3.  MINIMAL UNIT TEST (no HTTP)
|-------------------------------------------------------------------*/
public function test_service_class(): void
{
    $result = (new PriceService)->discount(100, 10);
    $this->assertSame(90.0, $result);
}

/* ------------------------------------------------------------------
|  4.  MINIMAL FEATURE TEST (HTTP end-point)
|-------------------------------------------------------------------*/
public function test_guest_can_see_homepage(): void
{
    $this->get('/')->assertStatus(200);
}

/* ------------------------------------------------------------------
|  5.  AUTHENTICATED ROUTE
|-------------------------------------------------------------------*/
public function test_logged_user_can_see_dashboard(): void
{
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user)
         ->get('/dashboard')
         ->assertOk()
         ->assertSee('Dashboard');
}

/* ------------------------------------------------------------------
|  6.  JSON API TEST
|-------------------------------------------------------------------*/
public function test_api_creates_resource(): void
{
    $user  = \App\Models\User::factory()->create();
    $payload = ['title' => 'Todo'];

    $this->actingAs($user, 'api')
         ->postJson('/api/todos', $payload)
         ->assertCreated()
         ->assertJsonFragment($payload);
}

/* ------------------------------------------------------------------
|  7.  FILE UPLOAD
|-------------------------------------------------------------------*/
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

public function test_avatar_upload(): void
{
    Storage::fake('public');              # prevent real writes

    $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

    $this->actingAs($user)
         ->post('/profile/avatar', ['avatar' => $file])
         ->assertSessionHasNoErrors();

    Storage::disk('public')->assertExists('avatars/'.$file->hashName());
}

/* ------------------------------------------------------------------
|  8.  MAIL / NOTIFICATION FAKES
|-------------------------------------------------------------------*/
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderShipped;

public function test_email_is_sent(): void
{
    Mail::fake();

    // ... code that should trigger mail

    Mail::assertSent(OrderShipped::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}

/* ------------------------------------------------------------------
|  9.  QUEUE FAKES
|-------------------------------------------------------------------*/
use Illuminate\Support\Facades\Queue;
use App\Jobs\ResizeImage;

public function test_job_is_pushed(): void
{
    Queue::fake();

    // ... code that dispatches job

    Queue::assertPushed(ResizeImage::class);
}

/* ------------------------------------------------------------------
| 10.  CUSTOM HELPERS (put in tests/TestHelpers.php trait)
|-------------------------------------------------------------------*/
trait TestHelpers
{
    protected function createAdmin(): \App\Models\User
    {
        return \App\Models\User::factory()->create(['role' => 'admin']);
    }

    protected function jsonAs($user, $method, $uri, array $data = [])
    {
        return $this->actingAs($user, 'api')
                    ->json($method, $uri, $data);
    }
}

/* ------------------------------------------------------------------
| 11.  RUNNING TESTS
|-------------------------------------------------------------------|
# All tests
php artisan test                    # Laravel 8+ wrapper (coloured, parallel)
./vendor/bin/phpunit               # vanilla PHPUnit

# Single class
php artisan test --filter=TheftInsuranceCustomerJourneyTest

# Single method
php artisan test --filter=test_complete_customer_journey

# With coverage (needs Xdebug/PCOV)
php artisan test --coverage-html coverage
*/

/* ------------------------------------------------------------------
| 12.  TROUBLESHOOTING QUICK WINS
|-------------------------------------------------------------------|
- `php artisan config:clear`           # if env values ignored
- `composer dump-autoload`             # if new test class not found
- `php artisan migrate --env=testing`  # if tables missing
- Add `RefreshDatabase`                # if data leaks between tests
- Use `Storage::fake()`                # if file assertions fail
- Use `WithoutMiddleware`              # if 403/Throttle interrupts
*/

/* ------------------------------------------------------------------
| 13.  CHEAT SHEET SUMMARY (copy to sticky note)
|-------------------------------------------------------------------|
1. extend TestCase
2. use RefreshDatabase, WithFaker
3. Storage::fake() / Mail::fake() / Queue::fake()
4. $this->actingAs($user)->postJson(...)->assertCreated();
5. php artisan test --filter=name
|==================================================================*/

-------------------------------------------------
HOW TO USE THE NOTE
-------------------------------------------------
1. Create a new project  
   laravel new demo && cd demo

2. Drop the cheat-sheet snippet inside `tests/CheatSheet.php`

3. Create your first test instantly  
   php artisan make:test ExampleTest  
   … then copy any block from the cheat-sheet (unit, API, file, mail, …) into `ExampleTest.php`

4. Run it  
   php artisan test --filter=ExampleTest

That’s it—every core Laravel testing utility, set-up instruction, and common assertion pattern is now on one page.
