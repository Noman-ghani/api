<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ServicesPricings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("services_pricings")) {
            Schema::create("services_pricings", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("service_id");
                $table->string("name", 50)->nullable();
                $table->unsignedInteger("duration")->nullable();
                $table->unsignedDecimal("price");
                $table->unsignedDecimal("special_price")->nullable();
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
        Schema::dropIfExists("services_pricings");
    }
}
