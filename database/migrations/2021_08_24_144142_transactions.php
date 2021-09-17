<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Transactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("transactions")) {
            Schema::create("transactions", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->text("transaction_num")->nullable();
                $table->string("status", 50);
                $table->unsignedBigInteger("payment_id");
                $table->string("invoice_id", 100);
                $table->string("bank_name", 100)->nullable();
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
        Schema::dropIfExists("transactions");
    }
}
