<?php

namespace App\Http\Controllers\Api\Global;

use Stripe\Stripe;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Http\Controllers\Controller;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{

    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'pickup_date' => 'required|date',
            'pickup_time' => 'required',
            'pickup_location' => 'required|string',
            'drop_location' => 'nullable|string',
            'trip_type' => 'required|in:Hourly,Pay Per Ride,Round Trip',
            'waiting_time' => 'nullable|integer|min:0', // Waiting time in minutes
            'full_name' => 'required|string',
            'phone_no' => 'required|string',
            'email' => 'required|email',
            'contact_name' => 'nullable|string',
            'contact_no' => 'nullable|string',
            'number_of_passengers_booked' => 'required|integer',
            'number_of_kids' => 'nullable|integer',
            'total_distance' => 'required|numeric|min:0',
            'total_duration' => 'required|numeric|min:0',
            'success_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Fetch the vehicle
        $vehicle = Vehicle::find($request->vehicle_id);

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        // Calculate the total price using the Vehicle model's method
        $totalPrice = $vehicle->calculateTripPrice(
            $request->trip_type,
            $request->total_distance,
            $request->total_duration,
            $request->waiting_time
        );

        // Convert totalPrice to cents for Stripe
        $unitAmount = (int)($totalPrice * 100); // Convert to cents (integer)

        $pickupDate = \Carbon\Carbon::parse($request->pickup_date)->format('Y-m-d H:i:s');
        // Create the booking
        $booking = Booking::create([
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => $vehicle->vehicle_name,
            'vehicle_model' => $vehicle->vehicle_model,
            'license_no' => $vehicle->license_no,
            'number_of_passengers' => $vehicle->number_of_passengers,
            'number_of_baggage' => $vehicle->number_of_baggage,
            'pickup_date' => $pickupDate,
            'pickup_time' => $request->pickup_time,
            'pickup_location' => $request->pickup_location,
            'drop_location' => $request->drop_location,
            'trip_type' => $request->trip_type,
            'total_distance' => $request->total_distance,
            'total_duration' => $request->total_duration,
            'waiting_time' => $request->waiting_time,
            'full_name' => $request->full_name,
            'phone_no' => $request->phone_no,
            'email' => $request->email,
            'contact_name' => $request->contact_name,
            'contact_no' => $request->contact_no,
            'number_of_passengers_booked' => $request->number_of_passengers_booked,
            'number_of_kids' => $request->number_of_kids,
            'total_amount' => $totalPrice,
            'payment_status' => 'pending',
        ]);

        // Payment integration with Stripe
        Stripe::setApiKey(config('STRIPE_SECRET'));

        try {


            // Construct success and cancel URLs with booking ID
            $successUrl = $request->success_url
            ? "{$request->success_url}?booking={$booking->id}"
            : url("/success?booking={$booking->id}");

            $cancelUrl = $request->cancel_url
            ? "{$request->cancel_url}?booking={$booking->id}"
            : url("/cancel?booking={$booking->id}");

            // Create the Stripe session with the updated unit_amount
            $session = Session::create([
                'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Booking #' . $booking->id,
                            ],
                            'unit_amount' => $unitAmount, // Use the integer value (in cents)
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => "$successUrl",
                'cancel_url' => "$cancelUrl",
            ]);

            // Update booking with session ID
            $booking->update(['stripe_session_id' => $session->id]);

            // Create payment record
            Payment::create([
                'payable_type' => Booking::class,
                'payable_id' => $booking->id,
                'amount' => $totalPrice,
                'payment_status' => 'pending',
                'gateway' => 'stripe',
                'payment_method' => 'stripe',
                'stripe_session_id' => $session->id,
            ]);

            return response()->json([
                'message' => 'Booking created successfully. Redirect to payment.',
                'payment_url' => $session->url,
                'booking' => $booking,
            ], 201);

        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



}
