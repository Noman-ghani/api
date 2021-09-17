<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Taxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("taxes")) {
            Schema::create("taxes", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->string("title", 100);
                $table->float("rate")->nullable();
                $table->unsignedBigInteger("tax_1")->nullable();
                $table->unsignedBigInteger("tax_2")->nullable();
                $table->unsignedBigInteger("tax_3")->nullable();
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
        Schema::dropIfExists("taxes");
    }
}
