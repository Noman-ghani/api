<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AppointmentHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("appointment_history")) {
            Schema::create("appointment_history", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("appointment_id");
                $table->enum("status", ["new", "confirmed", "arrived", "started", "completed", "cancelled", "no-show"])->nullable();
                $table->unsignedBigInteger("staff_id")->nullable();
                $table->text("description");
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
        Schema::dropIfExists("appointment_history");
    }
}
