<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehiclesTable extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_name')->nullable();
            $table->string('license_no')->nullable();
            $table->string('vehicle_status')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->integer('number_of_passengers')->nullable();
            $table->integer('number_of_baggage')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('color')->nullable();
            $table->string('power')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('length')->nullable();
            $table->string('transmission')->nullable();
            $table->json('extra_features')->nullable(); // Store as JSON for multiple selections
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
}
