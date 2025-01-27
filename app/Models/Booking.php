<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'vehicle_name',
        'vehicle_model',
        'license_no',
        'number_of_passengers',
        'number_of_baggage',
        'pickup_date',
        'pickup_time',
        'pickup_location',
        'drop_location',
        'full_name',
        'phone_no',
        'email',
        'contact_name',
        'contact_no',
        'number_of_passengers_booked',
        'number_of_kids',
        'status',
        'admin_notes',
        'booking_reference',
        'payment_status',
        'total_amount',
        'amount_paid',
        'trip_type',
        'total_duration',
        'total_distance',
        'waiting_time',
        'confirmed_at',
        'cancelled_at',
        'stripe_session_id',
    ];

    // Optional relationship to Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

        // Relationship to Payment
        public function payments()
        {
            return $this->hasMany(Payment::class);
        }
}
