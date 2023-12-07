<?php

namespace Database\Seeders;

use App\Models\Statuses;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Statuses::create([
            'status'=>'Active',
        ]);
        Statuses::create([
            'status'=>'Suspended',
        ]);
        Statuses::create([
            'status'=>'Ban',
        ]);
        Statuses::create([
            'status'=>'Deleted',
        ]);
        Statuses::create([
            'status'=>'Income',
        ]);
        Statuses::create([
            'status'=>'Accepted',
        ]);
        Statuses::create([
            'status'=>'Rejected',
        ]);
    }
}
