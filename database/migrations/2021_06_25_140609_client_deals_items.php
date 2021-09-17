<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClientDealsItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("client_deals_items")) {
            Schema::create("client_deals_items", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("client_deal_id");
                $table->unsignedBigInteger("service_id")->nullable();
                $table->unsignedBigInteger("product_id")->nullable();
                $table->unsignedInteger("quantity_available");
                $table->unsignedInteger("quantity_utilized");
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
        Schema::dropIfExists("client_deals_items");
    }
}
