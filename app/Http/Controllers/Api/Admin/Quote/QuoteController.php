<?php

namespace App\Http\Controllers\Api\Admin\Quote;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuoteController extends Controller
{
    /**
     * Get all quote requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Start building the query
        $query = QuoteRequest::query();

        // Global Search Filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('passenger_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%")
                  ->orWhere('pick_up_location', 'like', "%$search%")
                  ->orWhere('drop_off_location', 'like', "%$search%")
                  ->orWhere('vehicle', 'like', "%$search%")
                  ->orWhere('service_type', 'like', "%$search%")
                  ->orWhere('notes', 'like', "%$search%");
            });
        }

        // Filter by status (if provided)
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by service type (if provided)
        if ($request->has('service_type')) {
            $query->where('service_type', $request->input('service_type'));
        }

        // Filter by payment status (if provided)
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Filter by assigned_to (if provided)
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        // Filter by date range (if provided)
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('pick_up_date', [
                $request->input('start_date'),
                $request->input('end_date')
            ]);
        }

        // Paginate the results
        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $quotes = $query->paginate($perPage);

        return response()->json($quotes, 200);
    }

    /**
     * Get a specific quote request by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['message' => 'Quote request not found'], 404);
        }

        return response()->json(['quote' => $quote], 200);
    }

    /**
     * Create a new quote request.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passenger_name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'service_type' => 'required|string|max:100',
            'pick_up_date' => 'required|date',
            'pick_up_time' => 'required|date_format:H:i',
            'pick_up_location' => 'required|string|max:255',
            'drop_off_date' => 'nullable|date',
            'drop_off_time' => 'nullable|date_format:H:i',
            'drop_off_location' => 'required|string|max:255',
            'passengers' => 'required|integer|min:1',
            'vehicle' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'agree_to_email' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $quote = QuoteRequest::create($request->all());

        return response()->json(['message' => 'Quote request created successfully', 'quote' => $quote], 201);
    }

    /**
     * Update a quote request.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['message' => 'Quote request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'passenger_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:15',
            'email' => 'sometimes|email|max:255',
            'service_type' => 'sometimes|string|max:100',
            'pick_up_date' => 'sometimes|date',
            'pick_up_time' => 'sometimes|date_format:H:i',
            'pick_up_location' => 'sometimes|string|max:255',
            'drop_off_date' => 'nullable|date',
            'drop_off_time' => 'nullable|date_format:H:i',
            'drop_off_location' => 'sometimes|string|max:255',
            'passengers' => 'sometimes|integer|min:1',
            'vehicle' => 'sometimes|string|max:255',
            'notes' => 'nullable|string',
            'agree_to_email' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:Pending,Approved,Rejected,Completed',
            'admin_notes' => 'nullable|string',
            'assigned_to' => 'nullable|string',
            'quote_price' => 'nullable|numeric',
            'payment_status' => 'sometimes|string|in:Unpaid,Paid',
            'response_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $quote->update($request->all());

        return response()->json(['quote' => $quote, 'message' => 'Quote request updated successfully'], 200);
    }

    /**
     * Delete a quote request.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['message' => 'Quote request not found'], 404);
        }

        $quote->delete();

        return response()->json(['message' => 'Quote request deleted successfully'], 200);
    }

    /**
     * Update the status of a quote request.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['message' => 'Quote request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Pending,Approved,Rejected,Completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $quote->update(['status' => $request->status]);

        return response()->json(['quote' => $quote, 'message' => 'Status updated successfully'], 200);
    }

    /**
     * Assign a quote request to an admin.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignToAdmin(Request $request, $id)
    {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['message' => 'Quote request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $quote->update(['assigned_to' => $request->assigned_to]);

        return response()->json(['quote' => $quote, 'message' => 'Quote assigned to admin successfully'], 200);
    }

    /**
     * Update the payment status of a quote request.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $quote = QuoteRequest::find($id);

        if (!$quote) {
            return response()->json(['message' => 'Quote request not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|string|in:Unpaid,Paid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $quote->update(['payment_status' => $request->payment_status]);

        return response()->json(['quote' => $quote, 'message' => 'Payment status updated successfully'], 200);
    }
}
