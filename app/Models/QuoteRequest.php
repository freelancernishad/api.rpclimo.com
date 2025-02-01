<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        // Customer Fields
        'passenger_name',
        'phone',
        'email',
        'service_type',
        'pick_up_date',
        'pick_up_time',
        'pick_up_location',
        'drop_off_date',
        'drop_off_time',
        'drop_off_location',
        'passengers',
        'vehicle',
        'notes',
        'agree_to_email',

        // Admin Management Fields
        'status',
        'admin_notes',
        'assigned_to',
        'quote_price',
        'payment_status',
        'response_date',
    ];
}
