<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTripTypeToBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('trip_type', ['Hourly', 'Pay Per Ride', 'Round Trip'])
                  ->default('Pay Per Ride')
                  ->after('payment_status')
                  ->comment('Type of trip: Hourly, Pay Per Ride, or Round Trip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('trip_type');
        });
    }
}
