<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_name',
        'license_no',
        'vehicle_status',
        'vehicle_model',
        'number_of_passengers',
        'number_of_baggage',
        'price',
        'color',
        'power',
        'fuel_type',
        'length',
        'transmission',
        'extra_features',

        'hourly_rate',
        'minimum_hour',
        'surcharge_percentage_hourly',
        'base_fare_price', // Updated field name
        'rate_per_mile',
        'rate_per_minute',
        'surcharge_percentage',
        'waiting_charge_per_min', // Only for Round Trip
    ];

    protected $casts = [
        'extra_features' => 'array',
    ];

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function getFirstImageAttribute()
    {
        return $this->images()->select('vehicle_id', 'image_path')->first()->image_path ?? null;
    }

    /**
     * Calculate the total price for a trip based on the trip type, distance, duration, and waiting time.
     *
     * @param string $tripType The type of trip (Hourly, Pay Per Ride, Round Trip).
     * @param float $totalDistance The total distance of the trip in miles.
     * @param int $totalDuration The total duration of the trip in minutes.
     * @param int $waitingTime The waiting time in minutes (only applicable for Round Trip).
     * @return float The total price for the trip.
     */
    public function calculateTripPrice($tripType, $totalDistance, $totalDuration, $waitingTime = 0)
    {
        // Convert all input values to appropriate numeric types
        $totalDistance = (float) $totalDistance;
        $totalDuration = (int) $totalDuration;
        $waitingTime = (int) $waitingTime;

        // Convert duration from seconds to minutes
        $totalMinutes = $totalDuration;

        // Fetch vehicle pricing details, ensuring correct data types
        $hourlyRate = (float) $this->hourly_rate;
        $minimumHour = (int) $this->minimum_hour;
        $ratePerMile = (float) $this->rate_per_mile;
        $ratePerMinute = (float) $this->rate_per_minute;
        $baseFare = (float) $this->base_fare_price;
        $surchargePercentage = (float) $this->surcharge_percentage;
        $waitingChargePerMin = (float) $this->waiting_charge_per_min;

        switch ($tripType) {
            case 'Hourly':
                $totalHours = $totalMinutes / 60; // Convert minutes to hours

                if ($totalHours <= $minimumHour) {
                    $totalPrice = $hourlyRate * $minimumHour;
                } else {
                    $totalPrice = $hourlyRate * $totalHours;
                }

                // Apply hourly surcharge
                $hourlySurcharge = $totalPrice * ($this->surcharge_percentage_hourly / 100);
                $totalPrice += $hourlySurcharge;

                break;


            case 'Pay Per Ride':

                $distanceCost = $totalDistance * $ratePerMile;
                // Log::info("distanceCost $distanceCost");
                $timeCost = $totalMinutes * $ratePerMinute;
                // Log::info("timeCost $timeCost");
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($surchargePercentage / 100);
                // Log::info("surcharge $surcharge");
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge;
                // Log::info("totalPrice $totalPrice");
                break;

            case 'Round Trip':

                // Calculate distance and time cost
                $distanceCost = $totalDistance * $ratePerMile;
                $timeCost = $totalMinutes * $ratePerMinute;

                // Calculate surcharge
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($surchargePercentage / 100);

                // Calculate base total price
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge;

                // Round before applying round-trip multiplier
                $totalPrice = round($totalPrice, 2);

                // Calculate waiting cost
                $waitingCost = $waitingTime * $waitingChargePerMin;
                // Log::info("waitingTime: $waitingTime");
                // Log::info("waitingChargePerMin: $waitingChargePerMin");
                // Log::info("waitingCost: $waitingCost");
                // Log::info("totalPrice: $totalPrice");

                // Apply round-trip multiplier, then add waiting cost
                $totalPrice = ($totalPrice * 2) + $waitingCost;

                // Final rounding to ensure consistency
                $totalPrice = round($totalPrice, 2);

                // Log::info("Final Estimated Price: $totalPrice");







                // Round trip doubles distance and time costs
                // $distanceCost = ($totalDistance * $ratePerMile) * 2;
                // $timeCost = ($totalMinutes * $ratePerMinute) * 2;
                // $baseFare *= 2; // Double the base fare
                // $waitingCost = $waitingTime * $waitingChargePerMin;
                // Log::info("waitingTime $waitingTime");
                // Log::info("waitingChargePerMin $waitingChargePerMin");
                // Log::info("waitingCost $waitingCost");
                // $surcharge = ($baseFare + $distanceCost + $timeCost) * ($surchargePercentage / 100);
                // $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge + $waitingCost;
                break;

            default:
                throw new \InvalidArgumentException('Invalid trip type');
        }

        // Add extra charges to the total price
        $extraCharges = $this->calculateExtraCharges($totalPrice);
        $totalPrice += $extraCharges;

        return round($totalPrice, 2);
    }


    public function extraPricings()
    {
        return $this->hasMany(VehicleExtraPricing::class);
    }

    public function calculateExtraCharges($subtotal)
    {
        return $this->extraPricings->sum(fn($extra) => $extra->calculateCharge($subtotal));
    }

}
