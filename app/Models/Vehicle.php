<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

}
