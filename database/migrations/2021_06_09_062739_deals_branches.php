<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DealsBranches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("deals_branches")) {
            Schema::create("deals_branches", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("deal_id");
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
        Schema::dropIfExists("deals_branches");
    }
}
