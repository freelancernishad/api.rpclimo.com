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
    ];

    protected $casts = [
        'extra_features' => 'array',
    ];

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }

}
