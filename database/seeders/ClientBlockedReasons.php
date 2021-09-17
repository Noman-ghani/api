<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientBlockedReasons extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("client_blocked_reasons");

        DB::table("client_blocked_reasons")->insert([
            ["reason" => "Too many no shows"],
            ["reason" => "Too many late cancellations"],
            ["reason" => "Too many reschedules"],
            ["reason" => "Rude or inappropriate to staff"],
            ["reason" => "Refused to pay"],
            ["reason" => "Booked fake appointments"]
        ]);
    }
}
