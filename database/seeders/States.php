<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class States extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("states")->truncate();
        
        DB::table("states")->insert([
            ["name" => "Sindh", "country_id" => 1],
            ["name" => "Punjab", "country_id" => 1],
            ["name" => "Balochistan", "country_id" => 1],
            ["name" => "Khyber Pakhtunkhwa", "country_id" => 1],
            ["name" => "Islamabad Capital Territory", "country_id" => 1],
            ["name" => "Gilgit−Baltistan", "country_id" => 1],
            ["name" => "Azad Jammu and Kashmir", "country_id" => 1],
            
            ["name" => "Auckland", "country_id" => 2],
            ["name" => "Balgowlah", "country_id" => 2],
            ["name" => "Balmain", "country_id" => 2],
            ["name" => "Bankstown", "country_id" => 2],
            ["name" => "Camberwell", "country_id" => 2],
            ["name" => "Caulfield", "country_id" => 2],
            ["name" => "Chatswood", "country_id" => 2],
            ["name" => "Cheltenham", "country_id" => 2],
            ["name" => "Cherrybrook", "country_id" => 2],
            ["name" => "Clayton", "country_id" => 2],
            ["name" => "Collingwood", "country_id" => 2],
            ["name" => "Hawthorn", "country_id" => 2],
            ["name" => "Jannnali", "country_id" => 2],
            ["name" => "Knoxfield", "country_id" => 2],
            ["name" => "Melbourne", "country_id" => 2],
            ["name" => "Perth", "country_id" => 2],
            ["name" => "Queensland", "country_id" => 2],
            ["name" => "Tasmania", "country_id" => 2],
            ["name" => "Templestowe", "country_id" => 2],
            ["name" => "Victoria", "country_id" => 2],
            ["name" => "Wheeler", "country_id" => 2]
        ]);
    }
}
