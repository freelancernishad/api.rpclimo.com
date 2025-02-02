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
                // Convert total minutes to hours and apply minimum hour rule
                // $hours = max(ceil($totalMinutes / 60), $minimumHour);
                // $totalPrice = $hourlyRate * $hours;



                // $totalHours = $totalMinutes / 60;
                // $totalPrice = $hourlyRate * $totalHours;



                $totalHours = $totalMinutes / 60; // Convert minutes to hours

                if ($totalHours <= $minimumHour) {
                    $totalPrice = $hourlyRate * $minimumHour; // Charge for at least 2 hours
                } else {
                    $totalPrice = $hourlyRate * $totalHours; // Charge for the exact hours
                }


                break;

            case 'Pay Per Ride':

                $distanceCost = $totalDistance * $ratePerMile;
                Log::info("distanceCost $distanceCost");
                $timeCost = $totalMinutes * $ratePerMinute;
                Log::info("timeCost $timeCost");
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($surchargePercentage / 100);
                Log::info("surcharge $surcharge");
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge;
                Log::info("totalPrice $totalPrice");


                break;

            case 'Round Trip':
                // Round trip doubles distance and time costs
                $distanceCost = ($totalDistance * $ratePerMile) * 2;
                $timeCost = ($totalMinutes * $ratePerMinute) * 2;
                $baseFare *= 2; // Double the base fare
                $waitingCost = $waitingTime * $waitingChargePerMin;
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($surchargePercentage / 100);
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge + $waitingCost;
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
