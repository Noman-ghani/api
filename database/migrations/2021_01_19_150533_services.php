<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Services extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("services")) {
            Schema::create("services", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->string("title", 100);
                $table->boolean("is_package")->default(0);
                $table->unsignedInteger("treatment_type")->nullable();
                $table->unsignedBigInteger("category_id");
                $table->enum("available_for", ["male", "female"])->nullable();
                $table->string("description", 500)->nullable();
                $table->boolean("enable_online_booking")->default(1);
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
        Schema::dropIfExists("services");
    }
}
