<?php

namespace App\Http\Controllers\Api\Admin\Slider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SliderController extends Controller
{
    /**
     * Display a listing of the sliders.
     */
    public function index(Request $request)
    {
        // Check if 'type' query parameter is set
        $type = $request->query('type');

        // Filter by type if provided
        if ($type) {
            $sliders = Slider::latest()->get();
        } else {
            $sliders = Slider::latest()->get(); // No filter, get all sliders
            return response()->json($sliders, 200);
        }

        // Initialize arrays for formatted sliders
        $formattedSliders = [];
        $carouselImages = [];

        // Format the response based on the type
        foreach ($sliders as $slider) {
            // Set action to true for the type that is active in the request
            $action = $type && $slider->type == $type ? true : false;

            if ($slider->type == 'video') {
                // Add video slider data
                $formattedSliders[] = [
                    'id' => $slider->id,
                    'title' => $slider->title,
                    'action' => $action,  // Set action dynamically based on type
                    'video' => $slider->file,  // The path to the video
                ];
            }

            if ($slider->type == 'image') {
                // Collect all image sliders for the carousel
                $carouselImages[] = [
                    'id' => $slider->id,
                    'title' => $slider->title,
                    'img' => $slider->file,  // Assuming file path is the image URL
                ];
            }
        }

        // If there are image sliders, we include them under the carousel key
        if (count($carouselImages) > 0) {
            $formattedSliders[] = [
                'action' => $type == 'image' ? true : false,  // Set action dynamically for image type
                'carousel' => $carouselImages,  // Array of dynamic images
            ];
        }

        return response()->json($formattedSliders, 200);
    }




    /**
     * Store a newly created slider.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:10240', // Added webp support
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $slider = new Slider();
        $slider->title = $request->title;

        // Check the type of file by getting MIME type
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mimeType = $file->getMimeType();

            // Set type based on MIME type
            if (strpos($mimeType, 'image') !== false) {
                $slider->type = 'image';
            } elseif (strpos($mimeType, 'video') !== false) {
                $slider->type = 'video';
            } else {
                return response()->json(['status' => false, 'message' => 'Unsupported file type'], 422);
            }

            // Use the helper function to upload the file to S3
            $slider->file = uploadFileToS3($file, 'sliders');
        }

        $slider->save();

        return response()->json(['status' => true, 'message' => 'Slider created successfully', 'data' => $slider], 201);
    }


    /**
     * Display the specified slider.
     */
    public function show($id)
    {
        $slider = Slider::find($id);
        if (!$slider) {
            return response()->json(['status' => false, 'message' => 'Slider not found'], 404);
        }
        return response()->json(['status' => true, 'data' => $slider], 200);
    }

    /**
     * Update the specified slider.
     */
    public function update(Request $request, $id)
    {
        $slider = Slider::find($id);
        if (!$slider) {
            return response()->json(['status' => false, 'message' => 'Slider not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:10240', // Added webp support
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Update file if new one is uploaded
        if ($request->hasFile('file')) {
            // Delete old file from S3
            Storage::disk('s3')->delete($slider->file);

            $file = $request->file('file');
            $mimeType = $file->getMimeType();

            // Set type based on MIME type
            if (strpos($mimeType, 'image') !== false) {
                $slider->type = 'image';
            } elseif (strpos($mimeType, 'video') !== false) {
                $slider->type = 'video';
            } else {
                return response()->json(['status' => false, 'message' => 'Unsupported file type'], 422);
            }

            // Upload new file to S3
            $slider->file = uploadFileToS3($file, 'sliders');
        }

        $slider->update($request->only('title', 'type'));

        return response()->json(['status' => true, 'message' => 'Slider updated successfully', 'data' => $slider], 200);
    }


    /**
     * Remove the specified slider.
     */
    public function destroy($id)
    {
        $slider = Slider::find($id);
        if (!$slider) {
            return response()->json(['status' => false, 'message' => 'Slider not found'], 404);
        }

        // Delete file from S3
        Storage::disk('s3')->delete($slider->file);
        $slider->delete();

        return response()->json(['status' => true, 'message' => 'Slider deleted successfully'], 200);
    }
}
