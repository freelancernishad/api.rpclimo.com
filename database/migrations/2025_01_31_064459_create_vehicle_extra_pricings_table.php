<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('vehicle_extra_pricings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('name'); // e.g., Fuel, Service Charge, Tax
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 10, 2); // Percentage or fixed amount
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_extra_pricings');
    }
};
