<?php

namespace App\Http\Controllers\Api\Admin\Vehicle;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use App\Models\VehicleExtraPricing;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
        $vehicle_status = $request->input('vehicle_status');

        // Global search input
        $search = $request->input('search');

        // Trip parameters from the request (with default values if not provided)
        $defaultTripType = $request->input('trip_type', 'Hourly');
        $defaultDistance = $request->input('distance', 10);
        $defaultDuration = $request->input('duration', 60);
        $defaultWaitingTime = $request->input('waiting_time', 0);

        // Base query with vehicle relationships
        $query = Vehicle::with('images')->select(
            'id', 'vehicle_name', 'vehicle_model', 'license_no', 'vehicle_status',
            'number_of_passengers', 'number_of_baggage', 'hourly_rate', 'minimum_hour',
            'surcharge_percentage_hourly', 'rate_per_mile', 'rate_per_minute',
            'base_fare_price', 'surcharge_percentage', 'waiting_charge_per_min', 'extra_features'
        );

        // Apply search filters if provided
        if ($numberOfPassengers) {
            $query->where('number_of_passengers', '>=', $numberOfPassengers);
        }

        if ($numberOfBaggage) {
            $query->where('number_of_baggage', '>=', $numberOfBaggage);
        }


        if ($vehicle_status) {
            $query->where('vehicle_status', '=', $vehicle_status);
        }

        // Apply global search filter if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_name', 'LIKE', "%$search%")
                  ->orWhere('license_no', 'LIKE', "%$search%")
                  ->orWhere('vehicle_model', 'LIKE', "%$search%")
                  ->orWhere('number_of_passengers', 'LIKE', "%$search%")
                  ->orWhere('number_of_baggage', 'LIKE', "%$search%");
            });
        }

        // Order by latest and paginate results
        $vehicles = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Check if the authenticated user is an admin using the admin guard
        $isAdmin = Auth::guard('admin')->check();

        // Transform the collection and filter only if the user is NOT an admin
        $filteredVehicles = $vehicles->getCollection()->transform(function ($vehicle) use ($defaultTripType, $defaultDistance, $defaultDuration, $defaultWaitingTime) {
            $vehicle->image = $vehicle->first_image; // Assuming first_image is an accessor in the Vehicle model
            $vehicle->estimated_price = $vehicle->calculateTripPrice(
                $defaultTripType,
                $defaultDistance,
                $defaultDuration,
                $defaultWaitingTime
            );
            return $vehicle;
        });

        // If the user is NOT an admin, filter out vehicles with estimated_price == 0
        if (!$isAdmin) {
            $filteredVehicles = $filteredVehicles->filter(function ($vehicle) {
                return $vehicle->estimated_price > 0;
            });


            // Apply priority sorting based on closest match to passengers and baggage
            if ($numberOfPassengers || $numberOfBaggage) {
                $filteredVehicles = $filteredVehicles->sortBy(function ($vehicle) use ($numberOfPassengers, $numberOfBaggage) {
                    $passengerDiff = isset($numberOfPassengers) ? abs($vehicle->number_of_passengers - $numberOfPassengers) : 0;
                    $baggageDiff = isset($numberOfBaggage) ? abs($vehicle->number_of_baggage - $numberOfBaggage) : 0;

                    return $passengerDiff + $baggageDiff; // Lower sum means higher priority
                })->values(); // Reset collection keys
            }

        }

        // Manually re-paginate the filtered collection
        $filteredVehicles = new \Illuminate\Pagination\LengthAwarePaginator(
            $filteredVehicles->values(), // Reset keys
            $filteredVehicles->count(),
            $perPage,
            \Illuminate\Pagination\Paginator::resolveCurrentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Return JSON response
        return response()->json($filteredVehicles);
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
        $vehicle = Vehicle::with('images','extraPricings')->findOrFail($id);
        return response()->json($vehicle);
    }

    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

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
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Use validated data
        $vehicle->update($validator->validated());

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


        $validator = Validator::make($request->all(), [
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'exists:vehicle_images,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }


        // Delete existing images if any IDs are provided
        if ($request->has('deleted_ids')) {
            VehicleImage::whereIn('id', $request->deleted_ids)->delete();
        }

        // Upload new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filePath = uploadFileToS3($image, 'vehicle_images');
                VehicleImage::create([
                    'vehicle_id' => $vehicle->id,
                    'image_path' => $filePath,
                ]);
            }
        }

        return response()->json(['message' => 'Images updated successfully'], 200);
    }



    public function removeImage(Request $request, $imageId)
    {
        $image = VehicleImage::findOrFail($imageId);

        // Delete image from S3
        // deleteFileFromS3($image->image_path);

        // Remove from database
        $image->delete();

        return response()->json(['message' => 'Image removed successfully'], 200);
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

        // Validate main pricing fields
        $validator = Validator::make($request->all(), [
            'hourly_rate' => 'nullable|numeric',
            'minimum_hour' => 'nullable|numeric',
            'surcharge_percentage_hourly' => 'nullable|numeric',
            'base_fare_price' => 'nullable|numeric',
            'rate_per_mile' => 'nullable|numeric',
            'rate_per_minute' => 'nullable|numeric',
            'surcharge_percentage' => 'nullable|numeric',
            'waiting_charge_per_min' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Update base pricing in the `vehicles` table
        $vehicle->update($validator->validated());

        // Validate extra pricing fields using Validator
        $extraPricingValidator = Validator::make($request->all(), [
            'extra_pricings' => 'nullable|array',
            'extra_pricings.*.name' => 'required_with:extra_pricings|string',
            'extra_pricings.*.type' => 'required_with:extra_pricings|in:percentage,fixed',
            'extra_pricings.*.value' => 'required_with:extra_pricings|numeric|min:0',
        ]);

        if ($extraPricingValidator->fails()) {
            return response()->json(['status' => false, 'errors' => $extraPricingValidator->errors()], 422);
        }

        $validatedExtraPricing = $extraPricingValidator->validated();

        // Get existing extra pricing records for the vehicle
        $existingExtraPricingNames = $vehicle->extraPricings()->pluck('name')->toArray();

        // Extract new extra pricing names from the request
        $newExtraPricingNames = array_column($validatedExtraPricing['extra_pricings'] ?? [], 'name');

        // Identify extra pricings to delete (those that exist in DB but not in the request)
        $extraPricingsToDelete = array_diff($existingExtraPricingNames, $newExtraPricingNames);

        // Delete removed extra pricing records
        VehicleExtraPricing::where('vehicle_id', $vehicle->id)
            ->whereIn('name', $extraPricingsToDelete)
            ->delete();

        // Process each extra pricing entry (update or create)
        if (!empty($validatedExtraPricing['extra_pricings'])) {
            foreach ($validatedExtraPricing['extra_pricings'] as $extraPricingData) {
                VehicleExtraPricing::updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'name' => $extraPricingData['name'],
                    ],
                    [
                        'type' => $extraPricingData['type'],
                        'value' => $extraPricingData['value'],
                    ]
                );
            }
        }

        // Refresh vehicle data to include updated extra pricing
        $vehicle->load('extraPricings');

        return response()->json([
            'message' => 'Pricing details updated successfully',
            'data' => [
                'vehicle' => $vehicle,
                'extra_pricings' => $vehicle->extraPricings,
            ],
        ]);
    }




}
