<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BranchServices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("branch_services")) {
            Schema::create("branch_services", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("branch_id");
                $table->unsignedBigInteger("service_id");
                $table->unsignedBigInteger("tax_id")->nullable();
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
        Schema::dropIfExists("branch_services");
    }
}
