<?php

namespace App\Http\Controllers\Api\Admin\Vehicle;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();
        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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

        $vehicle = Vehicle::create($validated);

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
        $vehicle = Vehicle::findOrFail($id);
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
