<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('surcharge_percentage_hourly', 8, 2)->default(0)->after('minimum_hour');
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('surcharge_percentage_hourly');
        });
    }
};
