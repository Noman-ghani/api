<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Discounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("discounts")) {
            Schema::create("discounts", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->string("title", 100);
                $table->enum("type", ["percentage", "amount"]);
                $table->unsignedDecimal("value");
                $table->boolean("enable_for_service")->default(1);
                $table->boolean("enable_for_product")->default(1);
                $table->boolean("enable_for_voucher")->default(1);
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
        Schema::dropIfExists("discounts");
    }
}
