<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalDurationAndTotalDistanceToBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('total_duration')->nullable()->after('trip_type')->comment('Total duration of the trip in minutes');
            $table->decimal('total_distance', 12, 2)->nullable()->after('total_duration')->comment('Total distance of the trip in kilometers');
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
            $table->dropColumn('total_duration');
            $table->dropColumn('total_distance');
        });
    }
}
