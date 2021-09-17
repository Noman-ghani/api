<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClosedDatesBranches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("closed_dates_branches")) {
            Schema::create("closed_dates_branches", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("closed_dates_id");
                $table->unsignedBigInteger("branch_id");
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
        Schema::dropIfExists("closed_dates_branches");
    }
}
