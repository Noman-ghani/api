<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Countries extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("countries")->truncate();

        DB::table("countries")->insert([
            ["sortname" => "PK", "name" => "Pakistan", "phone_code" => 92, "phone_regex" => "(0?3(?:[0-46]\d|55)\d{7})", "phone_mask" => "### #######", "flag" => "&#127477;&#127472;", "currency" => "Rs.", "is_active" => 1],
            ["sortname" => "AU", "name" => "Australia", "phone_code" => 61, "phone_regex" => "\({0,1}((0|\+61)(2|4|3|7|8)){0,1}\){0,1}(\ |-){0,1}[0-9]{2}(\ |-){0,1}[0-9]{2}(\ |-){0,1}[0-9]{1}(\ |-){0,1}[0-9]{3}","phone_mask" => "### ### ###", "flag" => "&#x1F1E6;&#x1F1FA;", "currency" => "AUD", "is_active" => 1]
        ]);
    }
}
