<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Clients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("clients")) {
            Schema::create("clients", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->unsignedBigInteger("customer_id")->nullable();
                $table->string("first_name", 50);
                $table->string("last_name", 50);
                $table->unsignedBigInteger("phone_country_id");
                $table->string("phone_number", 30);
                $table->string("email", 255)->nullable();
                $table->string("address", 100)->nullable();
                $table->string("suburb", 100)->nullable();
                $table->unsignedBigInteger("state_id")->nullable();
                $table->unsignedBigInteger("city_id")->nullable();
                $table->unsignedInteger("zip_code")->nullable();
                $table->date("birthday")->nullable();
                $table->enum("gender", ["male", "female"])->nullable();
                $table->text("notes")->nullable();
                $table->boolean("is_blocked")->nullable();
                $table->unsignedInteger("block_reason_id")->nullable();
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
        Schema::dropIfExists("clients");
    }
}
