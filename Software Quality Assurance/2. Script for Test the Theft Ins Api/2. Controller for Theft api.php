<?php

namespace App\Http\Controllers\Frontend\TheftInsNew;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Model\TheftInsuranceNewClaim;
use App\Model\TheftInsuranceNewOrder;
use App\Model\TheftInsuranceQuotation;
use Illuminate\Support\Facades\Validator;
use App\Model\TheftInsuranceNew\TheftInsuranceNewPkgModel;

class TheftInsNewCusApiController extends Controller
{
    //
    /**
     * Store a new theft insurance quotation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeQuotation(Request $request)
    {



        // Validate the request
        $validated = $request->validate([
            'pkg_id' => 'required|exists:theft_insurance_new_pkgs,id',
            'property_name' => 'required|string|max:255',
            'property_type' => 'required|string|in:company,organization,office,home,commercial',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address_line' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'coverage_amount' => 'required|numeric|min:50000',
            'property_documents' => 'required',
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

        try {
            // Check if user is authenticated
            if (auth()->guard('api')->check()) {
                // User is logged in, save directly to database with user_id
                $quotation = new TheftInsuranceQuotation($quotationData);
                $quotation->user_id = auth()->guard('api')->id();
                $quotation->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Your Theft insurance quotation request has been submitted successfully!',
                    'data' => [
                        'id' => $quotation->id,
                        'reference_number' => $referenceNumber,
                        'premium_amount' => $premium,
                        'status' => 'pending'
                    ]
                ], 201);
            } else {
                // For API, we'll return a response with the reference number
                // The frontend can handle the login flow
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required to complete this request',
                    'temp_data' => [
                        'reference_number' => $referenceNumber,
                        'premium_amount' => $premium
                    ]
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all quotations for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listQuotations(Request $request)
    {
        // return response()->json("Hello");
        try {
            $user = Auth::user();
            $quotations = TheftInsuranceQuotation::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $quotations,
                'pagination' => [
                    'total' => $quotations->total(),
                    'per_page' => $quotations->perPage(),
                    'current_page' => $quotations->currentPage(),
                    'last_page' => $quotations->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quotations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show details of a specific quotation
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showDetails($id)
    {
        try {
            $user = Auth::user();
            $quotation = TheftInsuranceQuotation::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$quotation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quotation not found or access denied'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $quotation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quotation details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new order from quotation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        try {


            // Validate request
            $validator = Validator::make($request->all(), [
                'quotation_id' => 'required|exists:theft_insurance_new_quotations,id'
            ]);



            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }




            // Find the quotation
            $quotation = TheftInsuranceQuotation::findOrFail($request->quotation_id);

            // Check if this quotation belongs to the authenticated user
            if ($quotation->user_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create an order for this quotation'
                ], 403);
            }

            $pkg = TheftInsuranceNewPkgModel::where('id', $quotation->pkg_id)
                ->select('category_id', 'vat', 'discount')
                ->first();

            // Generate unique order reference ID
            $orderRefId = 'TIO-' . date('Ymd') . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);

            // Create a new order
            $order = new TheftInsuranceNewOrder();
            $order->order_ref_id = $orderRefId;
            $order->order_status = 'pending';
            $order->shuffle_key = '';
            $order->category_id = $pkg->category_id;
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
            $order->premium_amount = $quotation->premium_amount;

            // Calculate Discount
            $discountRate = $pkg->discount / 100;
            $discountAmount = $quotation->premium_amount * $discountRate;
            $order->discount = $discountAmount;

            // Calculate VAT
            $vatRate = $pkg->vat / 100;
            $vatAmount = $quotation->premium_amount * $vatRate;
            $order->vat = $vatAmount;

            // Calculate Net Amount
            $netAmount = $quotation->premium_amount - $discountAmount - $vatAmount;
            $order->admin_total_amount = $netAmount;

            // Save the order
            $order->save();

            // Update quotation status to 'ordered'
            $quotation->status = 'ordered';
            $quotation->save();

            return response()->json([
                'success' => true,
                'message' => 'Your order has been placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_ref_id' => $order->order_ref_id,
                    'order_status' => $order->order_status,
                    'policy_start_date' => $order->policy_start_date,
                    'policy_end_date' => $order->policy_end_date,
                    'premium_amount' => $order->premium_amount,
                    'discount' => $order->discount,
                    'vat' => $order->vat,
                    'total_amount' => $order->admin_total_amount
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of orders for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderList()
    {
        try {
            // Get all orders for the authenticated user
            $orders = TheftInsuranceNewOrder::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get details of a specific order
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderDetails($id)
    {
        try {
            // Get the order details
            $order = TheftInsuranceNewOrder::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Get package details
            $package = TheftInsuranceNewPkgModel::find($order->pkg_id);

            // Format address from JSON
            $address = json_decode($order->address_in, true);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'package' => $package ? [
                        'id' => $package->id,
                        'name' => $package->name,
                        'category_id' => $package->category_id,
                        'discount' => $package->discount,
                        'vat' => $package->vat
                    ] : null,
                    'address' => $address
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get policy details
     *
     * @param int $id Encrypted policy ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function policy($id)
    {
        try {
            // No need to decrypt in API - should receive actual ID
            $theftInsurance = TheftInsuranceNewOrder::findOrFail($id);

            // Check if policy belongs to authenticated user
            if ($theftInsurance->user_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this policy'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'policy' => $theftInsurance,
                    'user' => Auth::user()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve policy details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of policies for authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function policyList()
    {
        try {
            // First check if the model namespace is correct
            // Make sure to use the correct model namespace/path
            $policies = TheftInsuranceNewOrder::where('user_id', Auth::id())
                ->whereNotNull('policy_id')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $policies,
                'pagination' => [
                    'total' => $policies->total(),
                    'per_page' => $policies->perPage(),
                    'current_page' => $policies->currentPage(),
                    'last_page' => $policies->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve policy list',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Submit a new claim
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function claimSubmit(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'policy_id' => 'required|string',
                'incident_date' => 'required|date',
                'incident_time' => 'required|string',
                'incident_location' => 'required|string',
                'incident_description' => 'required|string',
                'theft_dept_called' => 'required|in:yes,no',
                'theft_dept_report' => 'required|in:yes,no',
                'damage_type' => 'required|string',
                'damage_description' => 'required|string',
                'estimated_loss' => 'required|numeric|min:1',
                'is_habitable' => 'required|in:yes,no',
                'emergency_measures' => 'required|in:yes,no',
                'measures_description' => 'nullable|string',
                'terms_agreement' => 'required|in:yes',
                // 'claim_form_file' => 'required|array',
                // 'claim_form_file.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
                'damage_photos' => 'required|array',
                'damage_photos.*' => 'file|mimes:jpg,jpeg,png|max:5120',
                'supporting_docs' => 'nullable|array',
                'supporting_docs.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }


            return response()->json([
                'success' => true,
                'message' => 'Claim submitted successfully'

            ]);

            // Verify policy exists and belongs to user
            $policy_id = $request->policy_id;
            $order = TheftInsuranceNewOrder::where('policy_id', $policy_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Policy not found or you do not have permission to access it'
                ], 404);
            }

            // Update the coverage used amount
            $claim_amount = $request->estimated_loss;
            $order->update([
                'coverage_used_amount' => ($order->coverage_used_amount + $claim_amount),
            ]);

            // Process claim files
            $claim_form_file = [];
            if ($request->hasFile('claim_form_file')) {
                foreach ($request->file('claim_form_file') as $file) {
                    $path = $file->store('theft-insurance/documents/claim', 'public');
                    $claim_form_file[] = $path;
                }
            }

            $damage_photos = [];
            if ($request->hasFile('damage_photos')) {
                foreach ($request->file('damage_photos') as $file) {
                    $path = $file->store('theft-insurance/documents/claim', 'public');
                    $damage_photos[] = $path;
                }
            }

            $supporting_docs = [];
            if ($request->hasFile('supporting_docs')) {
                foreach ($request->file('supporting_docs') as $file) {
                    $path = $file->store('theft-insurance/documents/claim', 'public');
                    $supporting_docs[] = $path;
                }
            }

            // Generate unique claim reference ID
            $claimRefId = 'TIC-' . date('Ymd') . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'), 0, 6);

            // Create new claim
            $claim = new TheftInsuranceNewClaim();
            $claim->policy_id = $policy_id;
            $claim->claimRefId = $claimRefId;
            $claim->claim_status = 'pending';
            $claim->claim_status_reason = 'N/A';
            $claim->incident_date = $request->incident_date;
            $claim->incident_time = $request->incident_time;
            $claim->incident_location = $request->incident_location;
            $claim->incident_description = $request->incident_description;
            $claim->theft_dept_called = $request->theft_dept_called;
            $claim->theft_dept_report = $request->theft_dept_report;
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

            return response()->json([
                'success' => true,
                'message' => 'Claim submitted successfully',
                'data' => [
                    'claim_id' => $claim->id,
                    'claim_ref_id' => $claim->claimRefId,
                    'claim_status' => $claim->claim_status,
                    'claim_amount' => $claim->claim_amount
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit claim',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of claims for authenticated user's policies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function claimList()
    {
        try {
            // Get all user's policies
            $userPolicies = TheftInsuranceNewOrder::where('user_id', Auth::id())
                ->pluck('policy_id')
                ->toArray();

            // Get claims for user's policies with pagination
            $claims = TheftInsuranceNewClaim::whereIn('policy_id', $userPolicies)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $claims,
                'pagination' => [
                    'total' => $claims->total(),
                    'per_page' => $claims->perPage(),
                    'current_page' => $claims->currentPage(),
                    'last_page' => $claims->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve claim list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get details of a specific claim
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function claimDetails($id)
    {
        try {
            $claim = TheftInsuranceNewClaim::findOrFail($id);

            // Check if claim belongs to current user's policy
            $policy = TheftInsuranceNewOrder::where('policy_id', $claim->policy_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$policy) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this claim'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'claim' => $claim,
                    'policy' => $policy
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve claim details',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
