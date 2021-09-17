<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Invoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("invoices")) {
            Schema::create("invoices", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->unsignedBigInteger("branch_id");
                $table->unsignedBigInteger("payment_method_id")->nullable();
                $table->timestamp("payment_created_at")->nullable();
                $table->timestamp("void_created_at")->nullable();
                $table->timestamp("refund_created_at")->nullable();
                $table->unsignedBigInteger("client_id")->nullable();
                $table->unsignedBigInteger("original_invoice_id")->nullable();
                $table->enum("status", ["completed", "unpaid", "voided", "refunded"]);
                $table->boolean("is_tax_inclusive")->default(0);
                $table->string("invoice_number", 100);
                $table->unsignedDecimal("grosstotal");
                $table->unsignedDecimal("discount");
                $table->unsignedDecimal("subtotal");
                $table->unsignedDecimal("tax")->nullable()->default(0);
                $table->unsignedDecimal("grandtotal");
                $table->unsignedDecimal("balance");
                $table->unsignedBigInteger("payment_received_by")->nullable();
                $table->mediumText("notes")->nullable();
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
        Schema::dropIfExists("invoices");
    }
}
