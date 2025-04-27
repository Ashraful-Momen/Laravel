<?php

namespace Tests\Feature;

use App\Model\TheftInsuranceNewClaim;
use App\Model\TheftInsuranceNewOrder;
use App\Model\TheftInsuranceQuotation;
use App\Model\TheftInsuranceNew\TheftInsuranceNewPkgModel;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TheftInsuranceCustomerJourneyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $pkg;

    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create fake storage disk for testing file uploads
        Storage::fake('public');
        
        // Create a test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '01712345678',
            'password' => bcrypt('password123'),
        ]);
        
        // Create a test insurance package
        $this->pkg = TheftInsuranceNewPkgModel::create([
            'category_id' => 1,
            'partner_code' => 'IN',
            'insurance_company_code' => 'PR',
            'b2b_b2c_code' => 'C',
            'vat' => 15,
            'discount' => 5,
            'name' => 'Premium Theft Insurance',
            'description' => 'Comprehensive coverage for theft incidents',
            'is_active' => true,
        ]);
    }

    /**
     * Test the complete customer journey flow from quotation to claim.
     *
     * @return void
     */
    public function test_complete_customer_journey()
    {
        // Section 1: Test quotation form submission
        $this->get_quotation_form();
        $quotation_id = $this->submit_quotation();
        
        // Section 2: Test viewing quotations
        $this->view_quotation_list();
        $this->view_quotation_details($quotation_id);
        
        // Section 3: Test creating an order
        $order_id = $this->create_order($quotation_id);
        
        // Section 4: Test viewing orders
        $this->view_order_list();
        $this->view_order_details($order_id);
        
        // Section 5: Test viewing policies
        $policy_id = $this->simulate_payment_and_get_policy_id($order_id);
        $this->view_policy_list();
        $this->view_policy_details($policy_id);
        
        // Section 6: Test claim creation and management
        $claim_id = $this->submit_claim($policy_id);
        $this->view_claim_list();
        $this->view_claim_details($claim_id);
    }
    
    /**
     * Test accessing the quotation form.
     *
     * @return void
     */
    protected function get_quotation_form()
    {
        // Act as authenticated user
        $response = $this->actingAs($this->user)
            ->get('/theft-insurance-form');
            
        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-quotation');
        $response->assertViewHas('fire_ins_pkg');
    }
    
    /**
     * Test submitting a quotation.
     *
     * @return int The created quotation ID
     */
    protected function submit_quotation()
    {
        // Create fake property documents
        $file1 = UploadedFile::fake()->image('document1.jpg');
        $file2 = UploadedFile::fake()->image('document2.jpg');
        
        // Prepare form data
        $formData = [
            'pkg_id' => $this->pkg->id,
            'property_name' => 'Test Property',
            'property_type' => 'home',
            'email' => 'test@example.com',
            'phone' => '01712345678',
            'address_line' => '123 Test Street',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1212',
            'coverage_amount' => 100000,
            'property_documents' => [$file1, $file2],
        ];
        
        // Submit the form
        $response = $this->actingAs($this->user)
            ->post('/theft-insurance/quotation', $formData);
            
        // Assert redirect and success message
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        
        // Get the created quotation
        $quotation = TheftInsuranceQuotation::where('user_id', $this->user->id)->latest()->first();
        
        // Assert quotation was created with correct data
        $this->assertNotNull($quotation);
        $this->assertEquals($this->pkg->id, $quotation->pkg_id);
        $this->assertEquals('Test Property', $quotation->property_name);
        $this->assertEquals('home', $quotation->property_type);
        $this->assertEquals(100000, $quotation->coverage_amount);
        
        // Return the quotation ID for use in subsequent tests
        return $quotation->id;
    }
    
    /**
     * Test viewing list of quotations.
     *
     * @return void
     */
    protected function view_quotation_list()
    {
        $response = $this->actingAs($this->user)
            ->get('/theft-insurance/quotations');
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-quotation-list');
        $response->assertViewHas('quotations');
    }
    
    /**
     * Test viewing details of a specific quotation.
     *
     * @param int $quotation_id The ID of the quotation to view
     * @return void
     */
    protected function view_quotation_details($quotation_id)
    {
        $encrypted_id = encrypt($quotation_id);
        
        $response = $this->actingAs($this->user)
            ->get("/theft-insurance/quotations/{$encrypted_id}");
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-quotation-details');
        $response->assertViewHas('quotation');
        $response->assertViewHas('documents');
    }
    
    /**
     * Test creating an order from a quotation.
     *
     * @param int $quotation_id The ID of the quotation
     * @return int The created order ID
     */
    protected function create_order($quotation_id)
    {
        $response = $this->actingAs($this->user)
            ->post('/theft-insurance/order/create', [
                'quotation_id' => $quotation_id
            ]);
            
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        
        // Get the created order
        $order = TheftInsuranceNewOrder::where('user_id', $this->user->id)->latest()->first();
        
        // Assert order was created with correct data
        $this->assertNotNull($order);
        $this->assertEquals($this->pkg->id, $order->pkg_id);
        $this->assertEquals('pending', $order->order_status);
        
        // Return the order ID for use in subsequent tests
        return $order->id;
    }
    
    /**
     * Test viewing list of orders.
     *
     * @return void
     */
    protected function view_order_list()
    {
        $response = $this->actingAs($this->user)
            ->get('/theft-insurance/order/list');
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-order-list');
        $response->assertViewHas('orders');
    }
    
    /**
     * Test viewing details of a specific order.
     *
     * @param int $order_id The ID of the order to view
     * @return void
     */
    protected function view_order_details($order_id)
    {
        $encrypted_id = encrypt($order_id);
        
        $response = $this->actingAs($this->user)
            ->get("/theft-insurance/order/details/{$encrypted_id}");
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-order-details');
        $response->assertViewHas('order');
    }
    
    /**
     * Simulate a successful payment and get the resulting policy ID.
     *
     * @param int $order_id The ID of the order
     * @return string The policy ID
     */
    protected function simulate_payment_and_get_policy_id($order_id)
    {
        // Get the order
        $order = TheftInsuranceNewOrder::findOrFail($order_id);
        
        // Generate a shuffle key (normally done during payment initiation)
        $shuffle_key = 'shuffle_'.uniqid();
        $order->shuffle_key = $shuffle_key;
        $order->save();
        
        // Simulate a payment response (normally done by the payment gateway callback)
        $paymentResponse = [
            'pgw_shuffle_id' => $shuffle_key,
            'pgw_status' => 'Complete',
            'pgw_response' => 'https://example.com/payment?payment_ref_id=123456',
            'pgw_name' => 'Test Gateway',
            'is_api' => 'false'
        ];

        // Call the static order_payment method (which we'll mock for testing)
        $controllerClassName = 'App\Http\Controllers\Frontend\TheftInsNew\TheftInsNewCusController';
        $controller = app()->make($controllerClassName);
        
        // Update the order directly to simulate the payment completion
        $order->pgw_status = 'Complete';
        $order->order_status = 'completed';
        
        // Generate a policy ID as the controller would
        $partnerCode = $this->pkg->partner_code ?? 'IN';
        $insuranceCode = $this->pkg->insurance_company_code ?? 'PR';
        $categoryCode = 'TI';
        $b2b_b2c = $this->pkg->b2b_b2c_code == 'B' ? '1' : '0';
        $year = substr(date('Y'), -2);
        $randomString = substr(str_shuffle('0123456789'), 0, 10);
        
        $policy_id = $partnerCode . $insuranceCode . $categoryCode . $b2b_b2c . $year . $randomString;
        
        $order->policy_id = $policy_id;
        $order->policy_start_date = now()->format('Y-m-d');
        $order->policy_end_date = now()->addYears(1)->format('Y-m-d');
        $order->save();
        
        return $policy_id;
    }
    
    /**
     * Test viewing list of policies.
     *
     * @return void
     */
    protected function view_policy_list()
    {
        $response = $this->actingAs($this->user)
            ->get('/theft-insurance/policies/list');
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-policy-list');
        $response->assertViewHas('order');
    }
    
    /**
     * Test viewing details of a specific policy.
     *
     * @param string $policy_id The policy ID to view
     * @return void
     */
    protected function view_policy_details($policy_id)
    {
        // Get the order with this policy ID
        $order = TheftInsuranceNewOrder::where('policy_id', $policy_id)->first();
        $encrypted_id = encrypt($order->id);
        
        $response = $this->actingAs($this->user)
            ->get("/theft-insurance/policy/{$encrypted_id}");
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-policy-certificate');
        $response->assertViewHas('fireInsurance');
    }
    
    /**
     * Test submitting a claim.
     *
     * @param string $policy_id The policy ID to claim against
     * @return int The created claim ID
     */
    protected function submit_claim($policy_id)
    {
        // Create test files for claim form
        $claimForm = UploadedFile::fake()->create('claim_form.pdf', 500);
        $damagePhoto1 = UploadedFile::fake()->image('damage1.jpg');
        $damagePhoto2 = UploadedFile::fake()->image('damage2.jpg');
        $supportDoc = UploadedFile::fake()->create('support.pdf', 500);
        
        // Encrypt policy ID as expected by controller
        $encrypted_policy_id = encrypt($policy_id);
        
        // Prepare claim form data
        $claimData = [
            'policy_id' => $encrypted_policy_id,
            'incident_date' => now()->subDays(2)->format('Y-m-d'),
            'incident_time' => '18:30',
            'incident_location' => 'Home',
            'incident_description' => 'A break-in occurred while I was away',
            'fire_dept_called' => 'yes',
            'fire_dept_report' => 'Police report #12345',
            'damage_type' => 'Broken window, stolen electronics',
            'damage_description' => 'Window was shattered, laptop and television were stolen',
            'estimated_loss' => 50000,
            'is_habitable' => 'yes',
            'emergency_measures' => 'yes',
            'measures_description' => 'Window has been temporarily boarded up',
            'terms_agreement' => 'yes',
            'claim_form_file' => [$claimForm],
            'damage_photos' => [$damagePhoto1, $damagePhoto2],
            'supporting_docs' => [$supportDoc]
        ];
        
        // Submit the claim
        $response = $this->actingAs($this->user)
            ->post('/theft-insurance/claim/submit', $claimData);
            
        $response->assertStatus(302);
        
        // Get the created claim
        $claim = TheftInsuranceNewClaim::where('policy_id', $policy_id)->latest()->first();
        
        // Assert claim was created with correct data
        $this->assertNotNull($claim);
        $this->assertEquals($policy_id, $claim->policy_id);
        $this->assertEquals('pending', $claim->claim_status);
        $this->assertEquals(50000, $claim->claim_amount);
        
        // Return the claim ID for use in subsequent tests
        return $claim->id;
    }
    
    /**
     * Test viewing list of claims.
     *
     * @return void
     */
    protected function view_claim_list()
    {
        $response = $this->actingAs($this->user)
            ->get('/theft-insurance/claims/list');
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-claim-list');
        $response->assertViewHas('claims');
    }
    
    /**
     * Test viewing details of a specific claim.
     *
     * @param int $claim_id The ID of the claim to view
     * @return void
     */
    protected function view_claim_details($claim_id)
    {
        $encrypted_id = encrypt($claim_id);
        
        $response = $this->actingAs($this->user)
            ->get("/theft-insurance/claim/details/{$encrypted_id}");
            
        $response->assertStatus(200);
        $response->assertViewIs('frontend.pages.theft_insurance_new.theft-insurance-claim-details');
        $response->assertViewHas('claim');
    }
    
    /**
     * Test updating documents for a rejected order.
     *
     * @return void
     */
    public function test_update_documents_for_rejected_order()
    {
        // Create a rejected order
        $order = TheftInsuranceNewOrder::create([
            'order_ref_id' => 'TIO-' . date('Ymd') . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6),
            'order_status' => 'Reject', // Status must be 'Reject' to test the update functionality
            'user_id' => $this->user->id,
            'pkg_id' => $this->pkg->id,
            'policy_start_date' => null,
            'policy_end_date' => null,
            'customer_phone' => '01712345678',
            'customer_email' => 'test@example.com',
            'address_in' => json_encode([
                'address_line' => '123 Test Street',
                'city' => 'Dhaka',
                'state' => 'Dhaka',
                'postal_code' => '1212'
            ]),
            'property_type' => 'home',
            'property_documents' => json_encode([]),
            'coverage_amount' => 100000,
            'premium_amount' => 5000,
            'admin_total_amount' => 4250,
            'vat' => 750,
            'discount' => 250
        ]);
        
        // Create fake updated documents
        $file1 = UploadedFile::fake()->image('updated_doc1.jpg');
        $file2 = UploadedFile::fake()->image('updated_doc2.jpg');
        
        // Submit updated documents
        $response = $this->actingAs($this->user)
            ->post("/theft-insurance/update-documents/{$order->id}", [
                'property_documents' => [$file1, $file2]
            ]);
            
        // Assert redirect and success message
        $response->assertStatus(302);
        
        // Reload the order from database
        $order->refresh();
        
        // Assert order status was updated
        $this->assertEquals('pending', $order->order_status);
        
        // Assert documents were updated
        $documents = json_decode($order->property_documents, true);
        $this->assertIsArray($documents);
        $this->assertNotEmpty($documents);
    }
}
