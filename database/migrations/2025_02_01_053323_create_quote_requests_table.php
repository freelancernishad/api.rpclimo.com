<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            // Customer Fields
            $table->string('passenger_name'); // Passenger Name
            $table->string('phone'); // Phone
            $table->string('email'); // Email
            $table->string('service_type'); // Service Type
            $table->date('pick_up_date'); // Pick-up Date
            $table->time('pick_up_time'); // Pick-up Time
            $table->string('pick_up_location'); // Pick-up Location
            $table->date('drop_off_date')->nullable(); // Drop-off Date (optional)
            $table->time('drop_off_time')->nullable(); // Drop-off Time (optional)
            $table->string('drop_off_location'); // Drop-off Location
            $table->unsignedInteger('passengers'); // Passengers (number of passengers, unsigned integer)
            $table->string('vehicle'); // Vehicle
            $table->text('notes')->nullable(); // Notes (optional)
            $table->boolean('agree_to_email')->default(false); // Agree to Email (checkbox)

            // Admin Management Fields
            $table->string('status')->default('Pending'); // Status (e.g., Pending, Approved, Rejected, Completed)
            $table->text('admin_notes')->nullable(); // Admin Notes
            $table->string('assigned_to')->nullable(); // Assigned To (Admin Name or Email)
            $table->decimal('quote_price', 10, 2)->nullable(); // Quote Price
            $table->string('payment_status')->default('Unpaid'); // Payment Status (e.g., Unpaid, Paid)
            $table->timestamp('response_date')->nullable(); // Response Date

            $table->timestamps(); // Created at and Updated at
        });
    }

    public function down()
    {
        Schema::dropIfExists('quote_requests');
    }
}
