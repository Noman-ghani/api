<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StaffShifts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("staff_shifts")) {
            Schema::create("staff_shifts", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("branch_id");
                $table->unsignedBigInteger("staff_id");
                $table->date("date_start");
                $table->date("date_end")->nullable();
                $table->unsignedTinyInteger("day_of_week");
                $table->enum("repeats", ["dont_repeat", "weekly"]);
                $table->enum("end_repeat", ["ongoing", "specific_date"])->nullable();
                $table->time("starts_at");
                $table->time("ends_at");
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
        Schema::dropIfExists("staff_shifts");
    }
}
