<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_infos', function (Blueprint $table) {
            DB::statement('ALTER TABLE course_infos ALTER COLUMN course_price_per_lesson TYPE NUMERIC USING course_price_per_lesson::NUMERIC');
        });
    }

    public function down(): void
    {
        Schema::table('course_infos', function (Blueprint $table) {
            //
        });
    }
};
