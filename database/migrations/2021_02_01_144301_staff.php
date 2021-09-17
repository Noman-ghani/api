<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Staff extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("staff")) {
            Schema::create("staff", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->unsignedBigInteger("user_id");
                $table->string("role", 50);
                $table->string("first_name", 50);
                $table->string("last_name", 50);
                $table->unsignedBigInteger("phone_country_id");
                $table->string("phone_number", 30);
                $table->enum("permission", ["basic", "low", "medium", "high"])->nullable();
                $table->string("staff_title", 100);
                $table->date("emp_start_date")->nullable();
                $table->date("emp_end_date")->nullable();
                $table->text("notes")->nullable();
                $table->boolean("enable_appointments");
                $table->string("appointment_color", 7)->nullable();
                $table->unsignedDecimal("service_commission")->nullable();
                $table->unsignedDecimal("product_commission")->nullable();
                $table->unsignedDecimal("deal_commission")->nullable();
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
        Schema::dropIfExists("staff");
    }
}
