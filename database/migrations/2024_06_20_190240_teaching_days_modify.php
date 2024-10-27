<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teaching_days', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });
        Schema::table('teaching_days', function (Blueprint $table) {
            $table->unsignedBigInteger('school_location_id');
            $table->foreign('school_location_id')->references('id')->on('school_locations');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_days', function (Blueprint $table) {
            //
        });
    }
};
