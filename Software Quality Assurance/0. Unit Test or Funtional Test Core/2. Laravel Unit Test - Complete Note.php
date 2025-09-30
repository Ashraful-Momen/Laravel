make it pass) → Refactor (improve code)
   
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
