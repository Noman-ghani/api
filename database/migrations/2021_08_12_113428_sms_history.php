<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SmsHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("sms_history")) {
            Schema::create("sms_history", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->text("mobile_number");
                $table->text("message");
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
        Schema::dropIfExists("sms_history");
    }
}
