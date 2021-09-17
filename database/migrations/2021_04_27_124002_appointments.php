<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Appointments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("appointments")) {
            Schema::create("appointments", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("business_id");
                $table->unsignedBigInteger("branch_id");
                $table->unsignedBigInteger("client_id")->nullable();
                $table->date("booking_date");
                $table->enum("status", ["new", "confirmed", "arrived", "started", "completed", "cancelled", "no-show"]);
                $table->unsignedBigInteger("cancel_reason_id")->nullable();
                $table->unsignedBigInteger("invoice_id")->nullable();
                $table->text("notes")->nullable();
                $table->enum("", ["marketplace", "portal"]);
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
        Schema::dropIfExists("appointments");
    }
}
