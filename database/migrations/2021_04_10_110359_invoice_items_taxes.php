<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvoiceItemsTaxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("invoice_items_taxes")) {
            Schema::create("invoice_items_taxes", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("invoice_id");
                $table->unsignedBigInteger("invoice_item_id");
                $table->unsignedBigInteger("tax_id");
                $table->string("title", 100);
                $table->float("rate");
                $table->unsignedBigInteger("amount");
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
        Schema::dropIfExists("invoice_items_taxes");
    }
}
