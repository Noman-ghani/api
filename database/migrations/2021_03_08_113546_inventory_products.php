<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InventoryProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("inventory_products")) {
            Schema::create("inventory_products", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->unsignedBigInteger("category_id")->nullable();
                $table->unsignedBigInteger("brand_id")->nullable();
                $table->string("name", 100);
                $table->string("description", 500)->nullable();
                $table->string("barcode", 50)->nullable();
                $table->string("sku", 50)->nullable();
                $table->unsignedDecimal("retail_price")->nullable();
                $table->unsignedDecimal("special_price")->nullable();
                $table->boolean("enable_commission")->default(1);
                $table->unsignedDecimal("supply_price")->nullable();
                $table->unsignedBigInteger("supplier_id")->nullable();
                $table->unsignedInteger("stock_on_hand")->nullable();
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
        Schema::dropIfExists("inventory_products");
    }
}
