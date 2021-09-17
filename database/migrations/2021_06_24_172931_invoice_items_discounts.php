<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvoiceItemsDiscounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("invoice_items_discounts")) {
            Schema::create("invoice_items_discounts", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("invoice_id");
                $table->unsignedBigInteger("invoice_item_id");
                $table->unsignedBigInteger("discount_id")->nullable();
                $table->string("title", 100);
                $table->enum("type", ["amount", "percentage"]);
                $table->unsignedDecimal("value");
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
        Schema::dropIfExists("invoice_items_discounts");
    }
}
