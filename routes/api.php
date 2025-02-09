<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Global\BookingController;
use App\Http\Controllers\Api\Admin\Quote\QuoteController;
use App\Http\Controllers\Api\Admin\Slider\SliderController;
use App\Http\Controllers\Api\Server\ServerStatusController;
use App\Http\Controllers\Api\Admin\Vehicle\VehicleController;
use App\Http\Controllers\Api\User\Package\UserPackageController;
use App\Http\Controllers\Api\User\PackageAddon\UserPackageAddonController;

// Load users and admins route files
if (file_exists($userRoutes = __DIR__.'/example.php')) {
    require $userRoutes;
}


if (file_exists($userRoutes = __DIR__.'/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/admins.php')) {
    require $adminRoutes;
}

if (file_exists($stripeRoutes = __DIR__.'/Gateways/stripe.php')) {
    require $stripeRoutes;
}



Route::get('/server-status', [ServerStatusController::class, 'checkStatus']);






// Route to get all packages with discounts (query params for discount_months)
Route::get('global/packages', [UserPackageController::class, 'index']);

// Route to get a single package by ID with discounts
Route::get('global/package/{id}', [UserPackageController::class, 'show']);

Route::prefix('global/')->group(function () {
    Route::get('package-addons/', [UserPackageAddonController::class, 'index']); // List all addons
    Route::get('package-addons/{id}', [UserPackageAddonController::class, 'show']); // Get a specific addon



        // Vehicle Routes
        Route::prefix('vehicles')->group(function () {
            // Get all vehicles
            Route::get('/', [VehicleController::class, 'index']);
            Route::get('/reservations/list', [VehicleController::class, 'index']);
            // Get a specific vehicle
            Route::get('/{vehicle}', [VehicleController::class, 'show']);
        });

        Route::post('vehicle/checkout', [BookingController::class, 'store']);

        Route::post('quote', [QuoteController::class, 'store']);

        Route::get('slider', [SliderController::class, 'index']);


});
