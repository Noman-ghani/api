<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentMethodsCountries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("payment_methods_countries")) {
            Schema::create("payment_methods_countries", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("payment_method_id");
                $table->unsignedBigInteger("country_id");
                $table->boolean("is_active")->default(1);
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
        Schema::dropIfExists("payment_methods_countries");
    }
}
