<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClientDeals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("client_deals")) {
            Schema::create("client_deals", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("client_id");
                $table->unsignedBigInteger("deal_id");
                $table->unsignedBigInteger("invoice_id");
                $table->timestamp("expires_at");
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
        Schema::dropIfExists("client_deals");
    }
}
