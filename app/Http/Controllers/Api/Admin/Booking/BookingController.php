<?php

namespace App\Http\Controllers\Api\Admin\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;

class BookingController extends Controller
{
    /**
     * Get a paginated list of bookings with limited columns.
     */
    public function index(Request $request)
    {
        $query = Booking::select([
            'id',
            'booking_reference',
            'full_name',
            'phone_no',
            'pickup_date',
            'pickup_time',
            'number_of_passengers',
            'number_of_baggage',
            'pickup_location',
            'drop_location',
            'status',
            'total_amount',
            'payment_status',
            'vehicle_name',
            'vehicle_model',
            'license_no'
        ])->where('payment_status', 'completed');

        // Global Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone_no', 'LIKE', "%{$search}%")
                    ->orWhere('pickup_date', 'LIKE', "%{$search}%")
                    ->orWhere('pickup_time', 'LIKE', "%{$search}%")
                    ->orWhere('number_of_passengers', 'LIKE', "%{$search}%")
                    ->orWhere('number_of_baggage', 'LIKE', "%{$search}%")
                    ->orWhere('vehicle_name', 'LIKE', "%{$search}%")
                    ->orWhere('vehicle_model', 'LIKE', "%{$search}%")
                    ->orWhere('license_no', 'LIKE', "%{$search}%");
            });
        }

        // Date Filters
        if ($request->has('filter')) {
            $filter = $request->input('filter');

            if ($filter == 'today') {
                $query->whereDate('created_at', today());
            } elseif ($filter == 'last_7_days') {
                $query->whereDate('created_at', '>=', now()->subDays(7));
            } elseif ($filter == 'last_month') {
                $query->whereMonth('created_at', now()->subMonth()->month);
            }
        }


        // Payment Status Filter (Paid/Unpaid)
        if ($request->has('payment_status')) {
            if ($request->input('payment_status') === 'paid') {
                $query->where('payment_status', 'completed');
            } elseif ($request->input('payment_status') === 'unpaid') {
                $query->where('payment_status', '!=', 'completed');
            }
        }

        $bookings = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 10)); // Default to 10 per page

        return response()->json($bookings);
    }



    /**
     * Get a single booking with full details.
     */
    public function show($id)
    {
        $booking = Booking::with(['vehicle', 'payments'])->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        return response()->json($booking);
    }

    /**
     * Update the status of a booking.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,cancelled,completed',
        ]);

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $booking->status = $request->status;

        if ($request->status === 'confirmed') {
            $booking->confirmed_at = now();
        } elseif ($request->status === 'cancelled') {
            $booking->cancelled_at = now();
        }

        $booking->save();

        return response()->json(['message' => 'Status updated successfully', 'booking' => $booking]);
    }
}
