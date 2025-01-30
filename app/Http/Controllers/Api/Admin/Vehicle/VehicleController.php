<?php

namespace App\Http\Controllers\Api\Admin\Vehicle;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        // Number of items per page (default: 10)
        $perPage = $request->input('per_page', 10);

        // Search parameters
        $numberOfPassengers = $request->input('number_of_passengers');
        $numberOfBaggage = $request->input('number_of_baggage');

        // Trip parameters from the request (with default values if not provided)
        $defaultTripType = $request->input('trip_type', 'Hourly'); // Default to 'Hourly' if not provided
        $defaultDistance = $request->input('distance', 10); // Default to 10 miles if not provided
        $defaultDuration = $request->input('duration', 60); // Default to 60 minutes if not provided
        $defaultWaitingTime = $request->input('waiting_time', 0); // Default to 0 minutes if not provided

        // Base query
        $query = Vehicle::with('images')->select('id', 'vehicle_name', 'vehicle_model', 'license_no', 'number_of_passengers', 'number_of_baggage','hourly_rate','minimum_hour','rate_per_mile','rate_per_minute','base_fare_price','surcharge_percentage','waiting_charge_per_min');

        // Apply search filters if provided
        if ($numberOfPassengers) {
            $query->where('number_of_passengers', '>=', $numberOfPassengers);
        }
        if ($numberOfBaggage) {
            $query->where('number_of_baggage', '>=', $numberOfBaggage);
        }

        // Order by latest items and paginate
        $vehicles = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage);

        Log::info($request->all());

        // Transform the collection to include the first image and calculated price
        $vehicles->getCollection()->transform(function ($vehicle) use ($defaultTripType, $defaultDistance, $defaultDuration, $defaultWaitingTime) {
            // Add the first image to the vehicle object
            $vehicle->image = $vehicle->first_image; // Assuming `first_image` is an accessor in the Vehicle model
            // Calculate the estimated price for the provided or default trip parameters
            $vehicle->estimated_price = $vehicle->calculateTripPrice(
                $defaultTripType,
                $defaultDistance,
                $defaultDuration,
                $defaultWaitingTime
            );
            Log::info($vehicle->estimated_price);

            return $vehicle;
        });

        // Return the paginated results
        return response()->json($vehicles);
    }


    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'vehicle_name' => 'nullable|string',
            'license_no' => 'nullable|string',
            'vehicle_status' => 'nullable|string',
            'vehicle_model' => 'nullable|string',
            'number_of_passengers' => 'nullable|integer',
            'number_of_baggage' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'color' => 'nullable|string',
            'power' => 'nullable|string',
            'fuel_type' => 'nullable|string',
            'length' => 'nullable|string',
            'transmission' => 'nullable|string',
            'extra_features' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Get validated data
        $validated = $validator->validated();

        // Create the vehicle
        $vehicle = Vehicle::create($validated);

        // Handle image uploads if present
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filePath = uploadFileToS3($image, 'vehicle_images');
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $filePath,
                ]);
            }
        }

        return response()->json($vehicle, 201);
    }




    public function show($id)
    {
        $vehicle = Vehicle::with('images')->findOrFail($id);
        return response()->json($vehicle);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'vehicle_name' => 'sometimes|string',
            'license_no' => 'sometimes|string',
            'vehicle_status' => 'sometimes|string',
            'vehicle_model' => 'sometimes|string',
            'number_of_passengers' => 'sometimes|integer',
            'number_of_baggage' => 'sometimes|integer',
            'price' => 'sometimes|numeric',
            'color' => 'sometimes|string',
            'power' => 'sometimes|string',
            'fuel_type' => 'sometimes|string',
            'length' => 'sometimes|string',
            'transmission' => 'sometimes|string',
            'extra_features' => 'sometimes|array',
        ]);

        $vehicle->update($validated);
        return response()->json($vehicle);
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();
        return response()->json(null, 204);
    }


    public function uploadImages(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filePath = uploadFileToS3($image, 'vehicle_images');
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $filePath,
                ]);
            }
        }

        return response()->json(['message' => 'Images uploaded successfully'], 200);
    }






        /**
     * Update pricing details for a specific vehicle.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePricing(Request $request, $id)
    {
        // Find the vehicle by ID
        $vehicle = Vehicle::findOrFail($id);

        // Validate the request data
        $validated = $request->validate([
            'hourly_rate' => 'nullable|numeric',
            'minimum_hour' => 'nullable|integer',
            'base_fare_price' => 'nullable|numeric',
            'rate_per_mile' => 'nullable|numeric',
            'rate_per_minute' => 'nullable|numeric',
            'surcharge_percentage' => 'nullable|numeric',
            'waiting_charge_per_min' => 'nullable|numeric',
        ]);

        // Update the vehicle's pricing details
        $vehicle->update($validated);

        // Return the updated vehicle
        return response()->json([
            'message' => 'Pricing details updated successfully',
            'data' => $vehicle,
        ]);
    }

}
