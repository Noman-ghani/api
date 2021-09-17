<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ServicesPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("services_packages")) {
            Schema::create("services_packages", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("package_id");
                $table->unsignedBigInteger("service_id");
                $table->unsignedBigInteger("pricing_id");
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
        Schema::dropIfExists("services_packages");
    }
}
