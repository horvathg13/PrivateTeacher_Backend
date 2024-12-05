<?php

namespace Database\Seeders;

use App\Models\Currencies;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrenciesSeeder extends Seeder
{
    public function run(): void
    {
        $json=file_get_contents("database/seeders/currencies.json");
        $currencies=json_decode($json,true);

        foreach ($currencies as $currency){
            DB::transaction(function () use($currency){
                Currencies::create([
                    "value"=>$currency['value'],
                    "label"=>$currency["label"]
                ]);
            });
        }
    }
}
