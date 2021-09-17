<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AppointmentItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("appointment_items")) {
            Schema::create("appointment_items", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("appointment_id");
                $table->enum("type", ["service"]);
                $table->unsignedBigInteger("service_id")->nullable();
                $table->time("start_time");
                $table->time("end_time");
                $table->unsignedInteger("duration");
                $table->unsignedBigInteger("staff_id");
                $table->unsignedDecimal("price");
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("appointment_items");
    }
}
