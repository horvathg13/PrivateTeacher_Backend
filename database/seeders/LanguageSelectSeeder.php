<?php

namespace Database\Seeders;

use App\Models\Languages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSelectSeeder extends Seeder
{
    public function run(): void
    {
        $json=file_get_contents("database/seeders/new_languages.json");
        $languages=json_decode($json,true);

        foreach ($languages as $language){
            DB::transaction(function () use($language){
                Languages::create([
                    "value"=>$language["value"],
                    "label"=>$language["label"]
                ]);
            });
        }
    }
}
