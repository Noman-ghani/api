<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SmsCampaignClients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("sms_campaign_clients")) {
            Schema::create("sms_campaign_clients", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("campaign_id");
                $table->unsignedBigInteger("client_id");
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
        Schema::dropIfExists("sms_campaign_clients");
    }
}
