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
            'drop_location' => 'required|string',
            'trip_type' => 'required|in:Hourly,Pay Per Ride,Round Trip',
            'waiting_time' => 'nullable|integer|min:0', // Waiting time in minutes
            'full_name' => 'required|string',
            'phone_no' => 'required|string',
            'email' => 'required|email',
            'contact_name' => 'nullable|string',
            'contact_no' => 'nullable|string',
            'number_of_passengers_booked' => 'required|integer',
            'number_of_kids' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Fetch the vehicle
        $vehicle = Vehicle::find($request->vehicle_id);

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'], 404);
        }

        // Calculate the total price based on trip type
        $tripType = $request->trip_type;
        $totalDistance = $request->input('total_distance', 0); // Total distance in miles
        $totalDuration = $request->input('total_duration', 0); // Total time in minutes
        $waitingTime = $request->input('waiting_time', 0); // Waiting time in minutes

        $totalPrice = 0;

        switch ($tripType) {
            case 'Hourly':
                $hours = max(ceil($totalDuration / 60), $vehicle->minimum_hour); // Calculate hours, ensure minimum hour
                $totalPrice = $vehicle->hourly_rate * $hours;
                break;

            case 'Pay Per Ride':
                $distanceCost = $totalDistance * $vehicle->rate_per_mile;
                $timeCost = $totalDuration * $vehicle->rate_per_minute;
                $baseFare = $vehicle->base_fare_price;
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($vehicle->surcharge_percentage / 100);
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge;
                break;

            case 'Round Trip':
                $distanceCost = $totalDistance * $vehicle->rate_per_mile * 2; // Round trip doubles the distance
                $timeCost = $totalDuration * $vehicle->rate_per_minute * 2; // Round trip doubles the time
                $baseFare = $vehicle->base_fare_price * 2; // Round trip doubles the base fare
                $waitingCost = $waitingTime * $vehicle->waiting_charge_per_min;
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($vehicle->surcharge_percentage / 100);
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge + $waitingCost;
                break;

            default:
                return response()->json(['error' => 'Invalid trip type'], 400);
        }

        // Create the booking
        $booking = Booking::create([
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => $vehicle->vehicle_name,
            'vehicle_model' => $vehicle->vehicle_model,
            'license_no' => $vehicle->license_no,
            'number_of_passengers' => $vehicle->number_of_passengers,
            'number_of_baggage' => $vehicle->number_of_baggage,
            'pickup_date' => $request->pickup_date,
            'pickup_time' => $request->pickup_time,
            'pickup_location' => $request->pickup_location,
            'drop_location' => $request->drop_location,
            'trip_type' => $tripType,
            'total_distance' => $totalDistance,
            'total_duration' => $totalDuration,
            'waiting_time' => $waitingTime,
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
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Booking #' . $booking->id,
                            ],
                            'unit_amount' => $totalPrice * 100, // Convert to cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => "http://localhost:8000/success?booking=$booking->id",
                'cancel_url' => "http://localhost:8000/cancel?booking=$booking->id",
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
