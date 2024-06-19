<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_infos', function (Blueprint $table) {
            $table->dropColumn('lang');
        });
        Schema::table('course_infos', function (Blueprint $table) {
            $table->string('lang');
        });
    }

    public function down(): void
    {
        Schema::table('course_infos', function (Blueprint $table) {
            //
        });
    }
};
