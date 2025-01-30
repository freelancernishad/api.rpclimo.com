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
        switch ($tripType) {
            case 'Hourly':
                $hours = max(ceil($totalDuration / 60), $this->minimum_hour); // Calculate hours, ensure minimum hour
                Log::info("hours : $hours");
                $totalPrice = $this->hourly_rate * $hours;
                break;

            case 'Pay Per Ride':
                $distanceCost = $totalDistance * $this->rate_per_mile;
                $timeCost = $totalDuration * $this->rate_per_minute;
                $baseFare = $this->base_fare_price;
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($this->surcharge_percentage / 100);
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge;
                break;

            case 'Round Trip':
                $distanceCost = $totalDistance * $this->rate_per_mile * 2; // Round trip doubles the distance
                $timeCost = $totalDuration * $this->rate_per_minute * 2; // Round trip doubles the time
                $baseFare = $this->base_fare_price * 2; // Round trip doubles the base fare
                $waitingCost = $waitingTime * $this->waiting_charge_per_min;
                $surcharge = ($baseFare + $distanceCost + $timeCost) * ($this->surcharge_percentage / 100);
                $totalPrice = $baseFare + $distanceCost + $timeCost + $surcharge + $waitingCost;
                break;

            default:
                throw new \InvalidArgumentException('Invalid trip type');
        }

        return $totalPrice;
    }
}
