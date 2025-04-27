
<?php

namespace App\Http\Controllers\Frontend\TheftInsNew;

use App\User;
use App\Helpers\UserInfo;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Model\TheftInsuranceNewClaim;
use App\Model\TheftInsuranceNewOrder;
use App\Model\TheftInsuranceQuotation;
use Illuminate\Support\Facades\Session;
use App\Notifications\TheftInsNewOrderNotification;
use App\Model\TheftInsuranceNew\TheftInsuranceNewPkgModel;

class TheftInsNewCusController extends Controller
{
    //

    public function showQuotationForm()
    {

        // --------------------------------- Session::data ------------------------------------
        $data = Session::all();



        $brand = array_search('yes', $data) ?? Session::put('Instasure', 'yes');

        $brand = array_search('yes', $data);

        if ($brand === false) {
            $brand = Session::put('Instasure', 'yes');
        }

        // --------------------------------- Session::data ------------------------------------

        // Check if there's a saved quotation in session to pre-fill the form
        $savedQuotation = session()->get('pending_fire_quotation');

        // $fire_ins_pkg = FireInsuranceNewPkgModel::select('id')->first()->id; //for avoid json object => $fire_ins_pkg = {'id': 1}.
        $fire_ins_pkg = TheftInsuranceNewPkgModel::latest()->first();
        // dd($fire_ins_pkg);


        return view('frontend/pages/theft_insurance_new/theft-insurance-quotation', compact('fire_ins_pkg', 'brand'));
        //  frontend/pages/profile

    }

    public function storeQuotation(Request $request)
    {

        // dd($request);
        // Validate the request
        $validated = $request->validate([
            'pkg_id' => 'required|exists:fire_insurance_new_pkgs,id',
            'property_name' => 'required|string|max:255',
            'property_type' => 'required|string|in:company,organization,office,home,commercial',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address_line' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'coverage_amount' => 'required|numeric|min:50000',
            'property_documents' => 'required|array',
            'property_documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB limit per file
        ]);

        // Calculate the premium
        $coverageAmount = $validated['coverage_amount'];
        $unitSize = 50000;
        $premiumPerUnit = 250;
        $units = ceil($coverageAmount / $unitSize);
        $premium = $units * $premiumPerUnit;

        // Generate a unique reference number
        $referenceNumber = 'TIQ-' . date('Ymd') . '-' . Str::random(6);

        // Save documents
        $documentPaths = [];
        if ($request->hasFile('property_documents')) {
            foreach ($request->file('property_documents') as $file) {
                $path = $file->store('theft-insurance/documents', 'public');
                $documentPaths[] = $path;
            }
        }

        // Prepare quotation data
        $quotationData = [
            'pkg_id' => $validated['pkg_id'],
            'reference_number' => $referenceNumber,
            'property_name' => $validated['property_name'],
            'property_type' => $validated['property_type'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'postal_code' => $validated['postal_code'],
            'coverage_amount' => $coverageAmount,
            'premium_amount' => $premium,
            'documents' => json_encode($documentPaths),
            'status' => 'pending',
        ];

        // Check if user is logged in
        if (auth()->check()) {
            // User is logged in, save directly to database with user_id
            $quotation = new TheftInsuranceQuotation($quotationData);
            $quotation->user_id = auth()->id();
            $quotation->save();

            $message = 'Your theft insurance quotation request has been submitted successfully! Reference Number: ' . $referenceNumber;

            // Return with success message
            return redirect()->route('theft-insurance.details', ['id' => encrypt($quotation->id)])->with('success', $message);
        } else {
            // User is not logged in, store in session
            session()->put('pending_theft_quotation', $quotationData);

            $message = 'Your quotation has been saved temporarily. Please login or register to complete your submission. Reference Number: ' . $referenceNumber;


            return redirect()->route('login')->with('success', $message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Display the list of quotations for the user.
     *
     * @return \Illuminate\Http\Response
     */
    public function listQuotations()
    {

        // dd("hellO");

        if (auth()->check()) {
            // For authenticated users, fetch their quotations from database
            $quotations = TheftInsuranceQuotation::where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // --------------------------------- Session::data ------------------------------------
            $data = Session::all();



            $brand = array_search('yes', $data) ?? Session::put('Instasure', 'yes');

            $brand = array_search('yes', $data);

            if ($brand === false) {
                $brand = Session::put('Instasure', 'yes');
            }



            // --------------------------------- Session::data ------------------------------------

            return view('frontend/pages/theft_insurance_new/theft-insurance-quotation-list', [
                'quotations' => $quotations,
                'brand' => $brand
            ]);
        } else {

            // --------------------------------- Session::data ------------------------------------
            $data = Session::all();



            $brand = array_search('yes', $data) ?? Session::put('Instasure', 'yes');

            $brand = array_search('yes', $data);

            if ($brand === false) {
                $brand = Session::put('Instasure', 'yes');
            }



            // --------------------------------- Session::data ------------------------------------
            // For non-authenticated users, check if they have a pending quotation in session
            $pendingQuotation = session()->get('pending_fire_quotation');

            return view('frontend/pages/theft_insurance_new/theft-insurance-quotation-list', [
                'quotations' => collect([]),
                'pendingQuotation' => $pendingQuotation,
                'brand' => $brand
            ]);
        }
    }

    /**
     * Display the details of a specific quotation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showDetails($id)
    {

        // dd($id);

        $id = decrypt($id);
        // Find the quotation
        $quotation = TheftInsuranceQuotation::findOrFail($id);

        // Check if user is authorized to view this quotation
        if (!auth()->check() || auth()->id() != $quotation->user_id) {
            return redirect()->route('theft-insurance.list')
                ->with('error', 'You are not authorized to view this quotation.');
        }

        // Parse documents JSON
        $documents = json_decode($quotation->documents, true) ?? [];

        return view('frontend/pages/theft_insurance_new/theft-insurance-quotation-details', [
            'quotation' => $quotation,
            'documents' => $documents,
        ]);
    }


    //create order
    public function createOrder(Request $request)
    {

        // dd($request);

        // Find the quotation
        $quotation = TheftInsuranceQuotation::findOrFail($request->quotation_id);

        $pkg = TheftInsuranceNewPkgModel::where('id', $quotation->pkg_id)->select('category_id', 'vat', 'discount')->first();

        // Generate unique order reference ID
        $orderRefId = 'TIO-' . date('Ymd') . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);

        // Create a new order
        $order = new TheftInsuranceNewOrder();
        $order->order_ref_id = $orderRefId;
        $order->order_status = 'pending';
        $order->shuffle_key = '';
        $order->category_id = $pkg->category_id; // Assuming 1 is for Fire Insurance
        $order->pkg_id = $quotation->pkg_id;
        $order->user_id = $quotation->user_id;
        $order->policy_start_date = date('Y-m-d'); // Today
        $order->policy_end_date = date('Y-m-d', strtotime('+1 year')); // One year from today
        $order->customer_phone = $quotation->phone;
        $order->customer_email = $quotation->email;
        $order->address_in = json_encode([
            'address_line' => $quotation->address_line,
            'city' => $quotation->city,
            'state' => $quotation->state,
            'postal_code' => $quotation->postal_code
        ]);
        $order->property_type = $quotation->property_type;
        $order->property_documents = $quotation->documents;
        $order->coverage_amount = $quotation->coverage_amount;
        $order_premium_amount = $quotation->premium_amount;
        // $order->admin_total_amount = $quotation->premium_amount;



        //Calculate Discount (assuming 10% - update as needed)
        $discountRate = $pkg->discount / 100;
        $discountAmount = $quotation->premium_amount * $discountRate;
        $order->discount = $discountAmount;

        //Calculate VAT without discount amount .
        $vatRate = $pkg->vat / 100;
        $vatAmount = $quotation->premium_amount * $vatRate;
        $order->vat = $vatAmount;

        //Calculate Net Amount
        $netAmount = $quotation->premium_amount - $discountAmount - $vatAmount;
        $order->admin_total_amount = $netAmount;

        //final premium amount:
        $order->premium_amount = $order_premium_amount - $discountAmount + $vatAmount;

        // dd($order->premium_amount, $order->admin_total_amount, $vatAmount, $discountAmount, $quotation->premium_amount);


        // Save the order
        $order->save();

        // Update quotation status to 'ordered'
        $quotation->status = 'ordered';

        $quotation->save();

        // return redirect()->back()->with('success', 'Your order has been placed successfully. Order Reference: ' . $order->order_ref_id);

        // Redirect with success message
        return redirect()->route('theft-insurance.order.details', ['id' => encrypt($order->id)])->with('success', 'Your order has been placed successfully. Order Reference: ' .  $order->order_ref_id);
    }



    //orders : list and Details views .
    public function orderList()
    {
        // Get all orders for the authenticated user
        $orders = TheftInsuranceNewOrder::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('frontend/pages/theft_insurance_new/theft-insurance-order-list', compact('orders'));
    }

    public function orderDetails($id)
    {

        $id = decrypt($id);
        // --------------------------------- Session::data ------------------------------------
        $brand = null;
        $data = Session::all();
        $brand = array_search('yes', $data);

        // Fix: Make sure $brand is always defined with a string value, not boolean false or null
        if ($brand === false || $brand === null) {
            Session::put('Instasure', 'yes');
            $brand = 'Instasure';
        }

        // dd($brand);


        // --------------------------------- Session::data ------------------------------------


        // Get the order details
        $order = TheftInsuranceNewOrder::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('frontend/pages/theft_insurance_new/theft-insurance-order-details', compact('order', 'brand'));
    }

    //order payment : __________________________________________

    public static function order_payment($response)
    {
        // Find the order according to the shuffle_key from pgw_shuffle_id
        $order = TheftInsuranceNewOrder::where('shuffle_key', $response['pgw_shuffle_id'])->first();

        // Check if order is not null before proceeding
        if ($order) {
            // Update payment information
            $order->pgw_status = $response['pgw_status'];
            $order->pgw_response = $response['pgw_response'];

            // If payment is complete and policy ID is not set, generate a policy ID
            if ($order->policy_id == null && $response['pgw_status'] == "Complete") {
                // Generate a random string for the policy ID
                $length = 10;
                $characters = '0123456789';
                $randomString = substr(str_shuffle($characters), 0, $length);

                // Get package details to build the policy number format
                $pkg = TheftInsuranceNewPkgModel::find($order->pkg_id);

                // Format: partnerCode(2) + insuranceCompanyCode(2) + CategoryCode(2) + b2b_b2c(1) + year(2) + randomString(10)
                $partnerCode = $pkg->partner_code ?? 'IN'; // Default to 'IN' for Instasure
                $insuranceCode = $pkg->insurance_company_code ?? 'PR'; // Default to 'PR' for Provider
                $categoryCode = 'TI'; // Fire Insurance code
                $b2b_b2c = $pkg->b2b_b2c_code == 'B' ? '1' : '0';

                // Get last 2 digits of the current year
                $year = substr(date('Y'), -2);

                // Combine all parts to create policy ID
                $policy_id = $partnerCode . $insuranceCode . $categoryCode . $b2b_b2c . $year . $randomString;

                // Set policy ID and dates
                $order->policy_id = $policy_id;
                $order->policy_start_date = now()->format('Y-m-d');
                $order->policy_end_date = now()->addYears(1)->format('Y-m-d');
            }

            // Update order status based on payment status
            $order->order_status = $response['pgw_status'] == "Complete" ? "completed" : "pending";

            // Save the updated order
            $order->save();

            // Get user information
            $user = User::find($order->user_id);

            // Extract payment reference ID from the response if needed
            $paymentRefId = null;
            if ($order->pgw_response) {
                $queryString = parse_url($order->pgw_response, PHP_URL_QUERY);
                if ($queryString) {
                    parse_str($queryString, $queryArray);
                    $paymentRefId = $queryArray['payment_ref_id'] ?? null;
                }
            }

            // Send SMS notification
            if ($user) {

                $sms_text = "প্রিয় " . $user->name . ", আপনার চুরির বীমা পলিসি সফলভাবে প্রক্রিয়া করা হয়েছে। বিস্তারিত দেখতে এখানে ক্লিক করুন: " . route('theft-insurance.order.list') . " ইনস্টাশিউর - ০৯৬৩৮১৮৮১৮৮";


                // $sms_text = "প্রিয় " . $user->name . ", আপনার অগ্নি বীমা পলিসি সফলভাবে প্রক্রিয়া করা হয়েছে। বিস্তারিত দেখতে এখানে ক্লিক করুন: " . route('fire-insurance.order.list') . " ইনস্টাশিউর - ০৯৬৩৮১৮৮১৮৮";

                // $sms_text = "Hello " . $user->name . ", Your Fire Insurance policy has been processed. Click here to view details: " . route('fire-insurance.order.list') . " Instasure - 09638188188";
                UserInfo::smsApi("88" . $user->phone, $sms_text);

                // Send email notification
                $user->notify(new TheftInsNewOrderNotification($user->name, $order));
            }


            if($response['is_api'] == "true"){
                return response()->json([
                    'user' => $user,
                    'order' => $order,
                    'pgw_name' => $response['pgw_name'],
                    'pgw_response' => $response['pgw_response'],
                    'pgw_status' => $response['pgw_status'],
                    'paymentRefId' => $paymentRefId,
                    'msg' => "Payment Status: " . ($response['pgw_status'] ?? $response['pgw_msg'])
                ]);
            }

            // Return view with all necessary data
            return view('frontend/pages/theft_insurance_new/theft-insurance-order-details', [
                'user' => $user,
                'order' => $order,
                'pgw_name' => $response['pgw_name'],
                'pgw_response' => $response['pgw_response'],
                'pgw_status' => $response['pgw_status'],
                'paymentRefId' => $paymentRefId,
                'msg' => "Payment Status: " . ($response['pgw_status'] ?? $response['pgw_msg'])
            ]);
        }




        // If order not found, handle the error
        return redirect()->route('theft-insurance.orders')->with('error', 'Order not found or payment could not be processed.');
    }



    //policy : ________________________________________________
    public function policy($id)
    {

        // dd($id);
        $id = decrypt($id);
        // dd($id);

        // policy id ;
        $user = Auth::user();
        $fireInsurance =  TheftInsuranceNewOrder::find($id);
        // dd($fireInsurance);
        // $all_order = $order;

        //  dd($order,$all_order); // policy id ;


        return view('frontend/pages/theft_insurance_new/theft-insurance-policy-certificate', ['user' => $user, 'fireInsurance' => $fireInsurance]);
    }

    public function policyList()
    {

        // dd("Hello");

        $user = Auth::user();
        $order =  TheftInsuranceNewOrder::where('user_id', $user->id)->latest()->get();



        return view('frontend/pages/theft_insurance_new/theft-insurance-policy-list', ['user' => $user, 'order' => $order]);
    }

      public function claimForm($policy_id)
    {

        $user = Auth::user();
        $order =  TheftInsuranceNewOrder::all();

        return view('frontend/pages/theft_insurance_new/theft-insurance-claim-submit-form', ['user' => $user, 'order' => $order, 'policy_id' => $policy_id]);
    }

    public function claimSubmit(Request $request)
    {

        // dd($request->all());

        $policy_id = decrypt($request->policy_id);

        // dd($policy_id);


        $order =  TheftInsuranceNewOrder::where('policy_id', $policy_id)->first();

        //update the coverage value : ______________________

        $claim_amount = $request->estimated_loss;

        // dd("remaining balance : ",$order->coverage_used_amount + $claim_amount);

        $order->update([
            'coverage_used_amount' => ($order->coverage_used_amount + $claim_amount),

        ]);

        // dd($order,$request->all());

        //----------------------------------------------------------------------------

        // claim file : ________________

        $claim_form_file = [];

        if ($request->hasFile('claim_form_file')) {
            foreach ($request->file('claim_form_file') as $file) {
                $path = $file->store('theft-insurance/documents/claim', 'public');
                $claim_form_file[] = $path;
            }
        }

        //----------------------------------------------------------------------------

        // damage_photos file : ________________

        $damage_photos = [];

        if ($request->hasFile('damage_photos')) {
            foreach ($request->file('damage_photos') as $file) {
                $path = $file->store('theft-insurance/documents/claim', 'public');
                $damage_photos[] = $path;
            }
        }

        //----------------------------------------------------------------------------

        // supporting_docs file : ________________

        $supporting_docs = [];

        if ($request->hasFile('supporting_docs')) {
            foreach ($request->file('supporting_docs') as $file) {
                $path = $file->store('theft-insurance/documents/claim', 'public');
                $supporting_docs[] = $path;
            }
        }

        //----------------------------------------------------------------------------


        // Generate unique order reference ID
        $claimRefId = 'TIC-' . date('Ymd') . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);


        // Create a new order
        $claim = new TheftInsuranceNewClaim();
        $claim->policy_id = $policy_id;
        $claim->claimRefId = $claimRefId;
        $claim->claim_status = 'pending';
        $claim->claim_status_reason = 'N/A';
        $claim->incident_date = $request->incident_date; // Assuming 1 is for Fire Insurance
        $claim->incident_time = $request->incident_time;
        $claim->incident_location = $request->incident_location;
        $claim->incident_description = $request->incident_description;
        $claim->theft_dept_called = $request->fire_dept_called;
        $claim->theft_dept_report = $request->fire_dept_report;
        $claim->damage_type = $request->damage_type;
        $claim->damage_description = $request->damage_description;
        $claim->claim_amount = $request->estimated_loss;
        $claim->is_habitable = $request->is_habitable;
        $claim->emergency_measures = $request->emergency_measures;
        $claim->measures_description = $request->measures_description;
        $claim->terms_agreement = $request->terms_agreement;

        $claim->claim_form_file = json_encode($claim_form_file);
        $claim->damage_photos = json_encode($damage_photos);
        $claim->supporting_docs = json_encode($supporting_docs);




        $claim->save();

        // return "submit successfully ";
        // // return redirect()->back()->with('success', 'Your order has been placed successfully. Order Reference: ' . $order->order_ref_id);

        // Redirect with success message
        return redirect()->route('theft-insurance.claim.detail', ['id' => encrypt($claim->id)])->with('success', 'Your order has been placed successfully. Order Reference: ' .  $order->order_ref_id);
    }


    public function claimList()
    {


        // Get all user's policies
        $userPolicies = TheftInsuranceNewOrder::where('user_id', Auth::id())
            ->pluck('policy_id')
            ->toArray();

        // Get claims for user's policies with pagination
        $claims = TheftInsuranceNewClaim::whereIn('policy_id', $userPolicies)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('frontend/pages/theft_insurance_new/theft-insurance-claim-list', compact('claims'));
    }

    public function claimDetails($id)
    {

        // dd($id);

        $id = decrypt($id);

        $claim = TheftInsuranceNewClaim::findOrFail($id);

        // Check if claim belongs to current user's policy
        $policy = TheftInsuranceNewOrder::where('policy_id', $claim->policy_id)
            ->where('user_id', Auth::id())
            ->first();

        // dd($policy);
        if (!$policy) {
            return redirect()->route('dashboard')->with('error', 'You don\'t have permission to view this claim.');
        }

        return view('frontend/pages/theft_insurance_new/theft-insurance-claim-details', compact('claim'));
    }

    //update the document if order status is rejected
    public function updateDocuments(Request $request, $id)
    {
        // Validate the request
        $request->validate([
            'property_documents' => 'required|array',
            'property_documents.*' => 'required|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'property_documents.required' => 'Please upload at least one document.',
            'property_documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
            'property_documents.*.max' => 'Documents must be less than 2MB in size.',
        ]);

        // dd($request);

        // Find the order
        $order = TheftInsuranceNewOrder::findOrFail($id);

        // Check if the order belongs to the logged-in user
        if (Auth::id() != $order->user_id) {
            return redirect()->back()->with('error', 'You are not authorized to update this order.');
        }

        // Check if the order status is rejected
        if ($order->order_status != 'Reject') {
            return redirect()->back()->with('error', 'You can only update documents for rejected orders.');
        }

        // Process the uploaded documents
        $documents = [];
        if ($request->hasFile('property_documents')) {
            foreach ($request->file('property_documents') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/theft_insurance/documents'), $fileName);
                $documents[] = 'uploads/theft_insurance/documents/' . $fileName;
            }
        }

        // Update the order with new documents
        $order->property_documents = json_encode($documents);
        $order->order_status = 'pending'; // Reset the status to pending for review
        $order->save();

        // Send notification to admin
        // You can use your existing notification system here

        // Return to the order details page
        return redirect()->route('theft-insurance.order.details', $order->id)->with('success', 'Your documents have been updated successfully and your order is now pending for review.');
    }






}
