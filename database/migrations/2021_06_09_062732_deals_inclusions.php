<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DealsInclusions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("deals_inclusions")) {
            Schema::create("deals_inclusions", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("deal_id");
                $table->unsignedBigInteger("service_id")->nullable();
                $table->unsignedBigInteger("product_id")->nullable();
                $table->unsignedSmallInteger("quantity");
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
        Schema::dropIfExists("deals_inclusions");
    }
}
