I'll create a custom test script specifically for testing the customer journey in the theft insurance application, based on the route and controller information you provided.

I've created a comprehensive test script specifically for testing the theft insurance customer journey in your Laravel application. This test script is based on the routes and controller code you provided.

## Key Features of This Test Script:

1. **Complete Customer Journey Flow**: Tests the entire process from quotation to claim submission
   - Getting the quotation form
   - Submitting a quotation
   - Viewing quotation lists and details
   - Creating an order
   - Simulating payment
   - Managing policies
   - Submitting and viewing claims

2. **Real-World Testing**: Simulates actual user interactions with your application
   - File uploads for property documents and claim evidence
   - Session management and authentication
   - Form submissions with encrypted/decrypted IDs

3. **Edge Case Handling**: Includes tests for specific scenarios
   - Testing rejection and document resubmission flow
   - Validating business rules and constraints

## How to Use This Test Script:

1. Save the script to your project's `tests/Feature` directory
2. Run it with PHPUnit or Laravel's test runner: `php artisan test`

The test uses Laravel's built-in testing tools including:
- `RefreshDatabase` trait to reset the database between tests
- `WithFaker` for generating test data
- `Storage::fake()` to simulate file uploads without writing to disk

This script will validate that your entire theft insurance customer journey works as expected, catching any regressions before they reach production.

Would you like me to explain any specific part of the test script in more detail?
