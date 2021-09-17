<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShortUrls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("short_urls")) {
            Schema::create("short_urls", function (Blueprint $table) {
                $table->id();
                $table->string("type", 50);
                $table->unsignedBigInteger("type_id");
                $table->string("url_code", 100);
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
        Schema::dropIfExists("short_urls");
    }
}
