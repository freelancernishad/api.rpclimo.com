<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPricingModelsToVehiclesTable extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Hourly Rate Pricing
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('extra_features');
            $table->integer('minimum_hour')->nullable()->after('hourly_rate');

            // Shared Pricing Fields (for Pay Per Ride and Round Trip)
            $table->decimal('base_fare_price', 8, 2)->nullable()->after('minimum_hour'); // Updated field name
            $table->decimal('rate_per_mile', 8, 2)->nullable()->after('base_fare_price');
            $table->decimal('rate_per_minute', 8, 2)->nullable()->after('rate_per_mile');
            $table->decimal('surcharge_percentage', 5, 2)->nullable()->after('rate_per_minute');

            // Round Trip Specific Field
            $table->decimal('waiting_charge_per_min', 8, 2)->nullable()->after('surcharge_percentage');
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop the columns if the migration is rolled back
            $table->dropColumn([
                'hourly_rate',
                'minimum_hour',
                'base_fare_price', // Updated field name
                'rate_per_mile',
                'rate_per_minute',
                'surcharge_percentage',
                'waiting_charge_per_min',
            ]);
        });
    }
}
