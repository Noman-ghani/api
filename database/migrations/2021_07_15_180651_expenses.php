<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Expenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("expenses_summary")) {
            Schema::create("expenses_summary", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->unsignedBigInteger("branch_id");
                $table->unsignedBigInteger("staff_id");
                $table->string("title", 100);
                $table->string("category", 100);
                $table->unsignedDecimal("price");
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
        Schema::dropIfExists("expenses_summary");
    }
}
