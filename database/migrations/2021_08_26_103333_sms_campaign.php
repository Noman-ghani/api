<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SmsCampaign extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("sms_campaign")) {
            Schema::create("sms_campaign", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->text("name");
                $table->text("message");
                $table->unsignedBigInteger("short_url_id")->nullable();
                $table->enum("status", ["draft", "complete"]);
                $table->binary("filter")->nullable();
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
        Schema::dropIfExists("sms_campaign");
    }
}
