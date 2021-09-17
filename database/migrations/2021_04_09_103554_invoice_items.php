<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvoiceItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("invoice_items")) {
            Schema::create("invoice_items", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("invoice_id");
                $table->unsignedBigInteger("product_id")->nullable();
                $table->unsignedBigInteger("service_id")->nullable();
                $table->unsignedBigInteger("deal_id")->nullable();
                $table->unsignedBigInteger("client_deal_id")->nullable();
                $table->unsignedBigInteger("staff_id")->nullable();
                $table->string("title", 100);
                $table->decimal("price");
                $table->unsignedInteger("quantity");
                $table->unsignedInteger("deal_quantity")->default(0);
                $table->unsignedDecimal("tax")->nullable()->default(0);
                $table->unsignedDecimal("discount")->nullable()->default(0);
                $table->unsignedTinyInteger("staff_commission_logic")->default(1)->comment("1 = Calculate by item sale price before discount - 2 = Calculate by item sale price including tax");
                $table->unsignedDecimal("staff_commission_rate")->default(0);
                $table->unsignedDecimal("staff_commission_value")->default(0);
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
        Schema::dropIfExists("invoice_items");
    }
}
