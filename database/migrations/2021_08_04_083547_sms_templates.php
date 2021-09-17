<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SmsTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("sms_templates")) {
            Schema::create("sms_templates", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->string("event", 50);
                $table->enum("send", ["immediately", "before", "after"]);
                $table->unsignedInteger("minutes")->nullable();
                $table->boolean("is_active")->default(1);
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
        Schema::dropIfExists("sms_templates");
    }
}