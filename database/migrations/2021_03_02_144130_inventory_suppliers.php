<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InventorySuppliers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("inventory_suppliers")) {
            Schema::create("inventory_suppliers", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->string("name", 100);
                $table->string("description", 500)->nullable();
                $table->string("first_name", 50);
                $table->string("last_name", 50);
                $table->unsignedBigInteger("phone_country_id")->nullable();
                $table->string("phone_number", 30)->nullable();
                $table->string("email", 255)->nullable();
                $table->string("street", 100)->nullable();
                $table->string("suburb", 100)->nullable();
                $table->unsignedBigInteger("state_id")->nullable();
                $table->unsignedBigInteger("city_id")->nullable();
                $table->unsignedInteger("zip_code")->nullable();
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
        Schema::dropIfExists("inventory_suppliers");
    }
}
