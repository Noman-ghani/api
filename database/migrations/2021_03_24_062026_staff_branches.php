<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StaffBranches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("staff_branches")) {
            Schema::create("staff_branches", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("staff_id");
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
        Schema::dropIfExists("staff_branches");
    }
}
