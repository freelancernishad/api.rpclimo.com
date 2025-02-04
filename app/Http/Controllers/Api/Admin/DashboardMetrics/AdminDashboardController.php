<?php

namespace App\Http\Controllers\Api\Admin\DashboardMetrics;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    /**
     * Display the dashboard metrics.
     *
     * @return JsonResponse
     */


     public function index(Request $request)
     {
         $year = $request->year ?? now()->year;
         $week = $request->week ?? 'current';

         $fromDate = $request->from_date;


         $toDate = isset($request->to_date) ? $request->to_date : $fromDate;





         // Total users
         $totalUsers = User::count();

         // New registrations in the last 7 days
         $newRegistrations = User::where('created_at', '>=', now()->subDays(7))->count();

         // Subscribed users
         $subscribedUsers = UserPackage::where('started_at', '<=', Carbon::now())
         ->where('ends_at', '>=', Carbon::now())
         ->with('user')  // Eager load the related user
         ->get()
         ->pluck('user')
         ->unique('id'); // Ensure unique users in case of multiple packages

        // To get the count of subscribed users
        $subscribedUserCount = $subscribedUsers->count();

         // Pending verifications
         $pendingVerifications = User::whereNull('email_verified_at')->count();

         // Package revenue data (monthly, yearly, weekly)
         $packageRevenueData = getPackageRevenueData($year, $week);

         // Total revenue by package
         $totalRevenueByPackage = $packageRevenueData['total_revenue_per_package'];

         // Weekly package revenue max value
         $weeklyPackageRevenueMax = $packageRevenueData['weekly_package_revenue_max'];

         // Calculate revenue by package within a date range if provided
         $revenueByDate = [];
         if ($fromDate) {
             $revenueByDate = Package::all()->map(function ($package) use ($fromDate, $toDate) {
                 // Query to get total revenue for the package within the specified date range or day
                 $totalAmountQuery = Payment::where('payable_type', 'Package')
                     ->where('payable_id', $package->id)
                     ->completed(); // Use the 'completed' scope to filter by completed payments

                 // Check if 'toDate' is undefined and apply appropriate date filter
                 if ($toDate === 'undefined') {
                     $fromDate = date("Y-m-d", strtotime($fromDate));
                     $totalAmountQuery->whereDate('paid_at', $fromDate);
                 } else {
                     $totalAmountQuery->whereBetween('paid_at', [$fromDate, $toDate]);
                 }

                 // Sum the total amount
                 $totalAmount = $totalAmountQuery->sum('amount');

                 return [
                     'name' => $package->package_name,
                     'total_amount' => (int) $totalAmount, // Cast to integer
                 ];
             })->toArray();
         }


        // Calculate total revenue across all packages
        $totalRevenue = Payment::where('payable_type', 'Package')
            ->completed()  // Use the 'completed' scope for completed payments
            ->sum('amount');


         return response()->json([
             'total_users' => $totalUsers,
             'new_registrations' => $newRegistrations,
             'subscribed_users' => $subscribedUserCount,
             'pending_verifications' => $pendingVerifications,
             'package_revenue' => $packageRevenueData['monthly_package_revenue'],
             'package_revenue_max' => $packageRevenueData['monthly_package_revenue_max'],
             'total_revenue_per_package' => $totalRevenueByPackage,
             'yearly_package_revenue' => $packageRevenueData['yearly_package_revenue'],
             'weekly_package_revenue' => $packageRevenueData['weekly_package_revenue'],
             'weekly_package_revenue_max' => $weeklyPackageRevenueMax,
             'revenue_by_date' => $revenueByDate, // Revenue by package within date range
             'total_revenue' => (int) $totalRevenue, // Total revenue across all packages
         ]);
     }



     public function dashboardMatrics()
     {
       // New Trips (Bookings) in the Last 7 Days (Completed Payments Only)
       $newTripsLast7Days = Booking::where('payment_status', 'completed')
       ->where('created_at', '>=', now()->subDays(7))
       ->count();

   // Active Vehicles
   $activeVehicles = Vehicle::where('vehicle_status', 'active')->count();

   // Income of Last Week (Only Completed Payments)
   $incomeLastWeek = Payment::where('status', 'completed')
       ->where('created_at', '>=', now()->subDays(7))
       ->sum('amount');

   // Order Flow of Last Week (Bookings with Completed Payments Only)
   $orderFlowLastWeek = Booking::where('payment_status', 'completed')
       ->where('created_at', '>=', now()->subDays(7))
       ->count();

   // New Vehicles List (Last 7 Days)
   $newVehicles = Vehicle::where('created_at', '>=', now()->subDays(7))->latest()->get();

   // Last 10 Orders (With Completed Payments)
   $last10Orders = Booking::where('payment_status', 'completed')
       ->latest()
       ->take(10)
       ->get();

   // Return Data as JSON
   return response()->json([
       'new_trips_last_7_days' => $newTripsLast7Days,
       'active_vehicles' => $activeVehicles,
       'income_last_week' => $incomeLastWeek,
       'order_flow_last_week' => $orderFlowLastWeek,
       'new_vehicles' => $newVehicles,
       'last_10_orders' => $last10Orders,
   ]);
     }


}
