<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Deals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("deals")) {
            Schema::create("deals", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->string("name", 100);
                $table->string("slug", 100);
                $table->mediumText("description")->nullable();
                $table->string("code", 20);
                $table->decimal("price");
                $table->unsignedBigInteger("tax_id")->nullable();
                $table->unsignedSmallInteger("limit")->nullable();
                $table->unsignedSmallInteger("utilized")->nullable()->default(0);
                $table->unsignedSmallInteger("expires_in_days")->nullable()->default(365);
                $table->dateTime("available_from");
                $table->dateTime("available_until");
                $table->boolean("enable_commission")->default(1);
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
        Schema::dropIfExists("deals");
    }
}
