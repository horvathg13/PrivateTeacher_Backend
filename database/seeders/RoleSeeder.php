<?php

namespace Database\Seeders;

use App\Models\Roles;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Roles::create([
            "name"=>'Admin'
        ]);
        Roles::create([
            "name"=>'Teacher'
        ]);
        Roles::create([
            "name"=>'Parent'
        ]);
        Roles::create([
            "name"=>'SchoolAdmin'
        ]);
    }
}
