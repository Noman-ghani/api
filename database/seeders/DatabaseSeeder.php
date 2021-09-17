<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ClientBlockedReasons::class);
        $this->call(Cities::class);
        $this->call(Countries::class);
        $this->call(States::class);
        $this->call(Timezones::class);
    }
}
