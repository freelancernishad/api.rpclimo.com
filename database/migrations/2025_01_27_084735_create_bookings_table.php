<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null'); // Optional foreign key
            $table->string('vehicle_name')->nullable(); // Nullable vehicle name
            $table->string('vehicle_model')->nullable(); // Nullable vehicle model
            $table->string('license_no')->nullable(); // Nullable license number
            $table->integer('number_of_passengers')->nullable(); // Nullable passenger capacity
            $table->integer('number_of_baggage')->nullable(); // Nullable baggage capacity
            $table->date('pickup_date')->nullable(); // Nullable pickup date
            $table->time('pickup_time')->nullable(); // Nullable pickup time
            $table->string('pickup_location')->nullable(); // Nullable pickup location
            $table->string('drop_location')->nullable(); // Nullable drop location
            $table->string('full_name')->nullable(); // Nullable full name
            $table->string('phone_no')->nullable(); // Nullable phone number
            $table->string('email')->nullable(); // Nullable email
            $table->string('contact_name')->nullable(); // Nullable contact name
            $table->string('contact_no')->nullable(); // Nullable contact number
            $table->integer('number_of_passengers_booked')->nullable(); // Nullable number of passengers for this booking
            $table->integer('number_of_kids')->nullable(); // Nullable number of kids for this booking

            // Additional columns for admin management
            $table->string('status')->default('pending'); // Booking status (e.g., pending, confirmed, completed, cancelled)
            $table->text('admin_notes')->nullable(); // Admin notes or comments
            $table->string('booking_reference')->unique()->nullable(); // Unique booking reference number
            $table->string('payment_status')->default('unpaid'); // Payment status (e.g., unpaid, paid, refunded)
            $table->decimal('total_amount', 10, 2)->nullable(); // Total amount for the booking
            $table->decimal('amount_paid', 10, 2)->nullable(); // Amount paid by the customer
            $table->dateTime('confirmed_at')->nullable(); // Timestamp when the booking was confirmed
            $table->dateTime('cancelled_at')->nullable(); // Timestamp when the booking was cancelled
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
