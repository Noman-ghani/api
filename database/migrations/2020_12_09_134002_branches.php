<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Branches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("branches")) {
            Schema::create("branches", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->string("name", 100);
                $table->string("slug", 100);
                $table->mediumText("description")->nullable();
                $table->string("email", 255)->nullable();
                $table->unsignedBigInteger("phone_country_id");
                $table->string("phone_number", 30);
                $table->string("address", 100);
                $table->unsignedBigInteger("state_id");
                $table->unsignedBigInteger("city_id");
                $table->string("business_type_1")->nullable();
                $table->string("business_type_2")->nullable();
                $table->string("business_type_3")->nullable();
                $table->unsignedBigInteger("product_tax_id")->nullable();
                $table->unsignedBigInteger("service_tax_id")->nullable();
                $table->string("invoice_prefix", 50)->nullable();
                $table->unsignedInteger("next_invoice_number")->default(1);
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
        Schema::dropIfExists("branches");
    }
}
