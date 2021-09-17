<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Countries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("countries")) {
            Schema::create("countries", function (Blueprint $table) {
                $table->id();
                $table->string("sortname", 3);
                $table->string("name", 150);
                $table->unsignedInteger("phone_code");
                $table->text("phone_regex")->nullable();
                $table->text("phone_mask")->nullable();
                $table->string("flag", 50)->nullable();
                $table->string("currency", 10)->nullable();
                $table->boolean("is_active")->default(1);
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
        Schema::dropIfExists("countries");
    }
}
