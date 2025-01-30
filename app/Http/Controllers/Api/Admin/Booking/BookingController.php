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
        $bookings = Booking::select([
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
                'payment_status'
            ])
            ->where(['payment_status'=>'completed'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 10)); // Default 10 per page

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
