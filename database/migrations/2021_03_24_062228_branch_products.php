<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BranchProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("branch_products")) {
            Schema::create("branch_products", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("branch_id");
                $table->unsignedBigInteger("product_id");
                $table->unsignedBigInteger("tax_id")->nullable();
                $table->unsignedInteger("stock_on_hand")->default(0);
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
        Schema::dropIfExists("branch_products");
    }
}
