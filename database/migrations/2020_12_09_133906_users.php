<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("users")) {
            Schema::create("users", function (Blueprint $table) {
                $table->id();
                $table->string("email", 255);
                $table->string("password", 255);
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
        Schema::dropIfExists("users");
    }
}
