<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CancellationReasons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("cancellation_reasons")) {
            Schema::create("cancellation_reasons", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->text("reason");
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
        Schema::dropIfExists("cancellation_reasons");
    }
}
