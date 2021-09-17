<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BranchTimings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("branch_timings")) {
            Schema::create("branch_timings", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("branch_id");
                $table->unsignedTinyInteger("day_of_week");
                $table->boolean("is_closed")->default(0);
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
        Schema::dropIfExists("branch_timings");
    }
}
