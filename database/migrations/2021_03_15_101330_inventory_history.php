<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InventoryHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("inventory_history")) {
            Schema::create("inventory_history", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->unsignedBigInteger("product_id");
                $table->unsignedBigInteger("branch_id")->nullable();
                $table->unsignedBigInteger("staff_id");
                $table->string("reason", 100)->nullable();
                $table->text("description")->nullable();
                $table->enum("action", ["+", "-"])->nullable();
                $table->unsignedInteger("quantity")->nullable();
                $table->unsignedDecimal("cost_price")->nullable()->default(0);
                $table->unsignedBigInteger("invoice_id")->nullable();
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
        Schema::dropIfExists("inventory_history");
    }
}
