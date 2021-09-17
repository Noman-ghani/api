<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Businesses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("businesses")) {
            Schema::create("businesses", function (Blueprint $table) {
                $table->id();
                $table->string("name", 100);
                $table->string("slug", 100);
                $table->mediumText("description")->nullable();
                $table->unsignedBigInteger("country_id");
                $table->unsignedBigInteger("time_zone_id");
                $table->enum("time_format", ["12h", "24h"]);
                $table->enum("week_start", ["0", "1", "2", "3", "4", "5", "6"]);
                $table->string("website", 512)->nullable();
                $table->string("facebook", 512)->nullable();
                $table->string("instagram", 512)->nullable();
                $table->string("linkedin", 512)->nullable();
                $table->boolean("is_tax_inclusive")->nullable();
                $table->unsignedTinyInteger("staff_commission_logic")->default(1)->comment("1 = Calculate by item sale price before discount - 2 = Calculate by item sale price including tax");
                $table->boolean("is_active")->default(1);
                $table->boolean("is_profile_complete")->nullable()->default(0);
                $table->unsignedMediumInteger("sms_limit")->nullable()->default(0);
                $table->unsignedBigInteger("default_branch_id")->nullable();
                $table->timestamp("subscription_expires_at")->nullable();
                $table->enum("subscription_package", ["standard", "premium"])->default("standard");
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
        Schema::dropIfExists("businesses");
    }
}
