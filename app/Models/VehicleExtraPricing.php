<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleExtraPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'name', // Name of the extra charge (Fuel, Service Charge, Tax)
        'type', // 'percentage' or 'fixed'
        'value', // Holds either percentage or fixed amount
    ];

    /**
     * Relationship: Each extra pricing belongs to a Vehicle.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Calculate the additional charge based on the given subtotal.
     *
     * @param float $subtotal The base subtotal for the trip.
     * @return float The calculated charge.
     */
    public function calculateCharge($subtotal)
    {
        return $this->type === 'percentage'
            ? ($subtotal * ($this->value / 100))
            : $this->value;
    }
}
