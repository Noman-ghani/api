<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Customers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("customers")) {
            Schema::create("customers", function (Blueprint $table) {
                $table->id();
                $table->string("first_name", 50);
                $table->string("last_name", 50);
                $table->string("email", 255);
                $table->string("password", 255)->nullable();
                $table->unsignedBigInteger("time_zone_id")->nullable();
                $table->unsignedBigInteger("phone_country_id")->nullable();
                $table->string("phone_number", 30)->nullable();
                $table->date("birthday")->nullable();
                $table->enum("gender", ["male", "female"])->nullable();
                $table->boolean("from_facebook")->default(0);
                $table->boolean("from_google")->default(0);
                $table->string("reset_password_token", 255)->nullable();
                $table->timestamp("reset_password_expires_on")->nullable();
                $table->boolean("is_email_verified")->default(0);
                $table->timestamp("email_verified_on")->nullable();
                $table->string("email_verification_token", 255)->nullable();
                $table->timestamp("email_verification_expires_on")->nullable();
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
        Schema::dropIfExists("customers");
    }
}
