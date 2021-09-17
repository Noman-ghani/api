<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClosedDates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("closed_dates")) {
            Schema::create("closed_dates", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->timestamp("starts_at");
                $table->timestamp("ends_at");
                $table->string("description", 100);
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
        Schema::dropIfExists("closed_dates");
    }
}
