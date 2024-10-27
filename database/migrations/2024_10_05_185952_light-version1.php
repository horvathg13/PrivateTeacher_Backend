<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_locations', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('course_infos');
            $table->foreign('location_id')->references('id')->on('locations');
        });
        Schema::table('teaching_days', function (Blueprint $table) {
            $table->renameColumn('school_location_id', 'course_location_id');
        });
        Schema::table('teaching_days', function (Blueprint $table) {
            $table->foreign('course_location_id')->references('id')->on('course_locations');
        });
    }

    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            //
        });
    }
};
