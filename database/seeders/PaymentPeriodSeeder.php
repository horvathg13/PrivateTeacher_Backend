<?php

namespace Database\Seeders;

use App\Models\PaymentPeriods;
use Illuminate\Database\Seeder;

class PaymentPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentPeriods::create([
            'period'=>'lesson',
        ]);
        PaymentPeriods::create([
            'period'=>'monthly',
        ]);
        PaymentPeriods::create([
            'period'=>'annually',
        ]);
    }
}
