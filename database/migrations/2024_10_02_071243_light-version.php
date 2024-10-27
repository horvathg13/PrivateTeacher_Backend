<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('location_id');
        });

        Schema::table('teaching_days', function (Blueprint $table) {
            $table->dropColumn(['start','end']);
            $table->dropForeign('teaching_days_school_location_id_foreign');
        });

        Schema::table('teacher_course_requests', function (Blueprint $table) {
            $table->dropColumn(['from','to']);
        });

        Schema::table('course_infos', function (Blueprint $table) {
            $table->dropColumn(['double_time', 'school_location_id', 'school_year_id']);
        });


        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['canceled_class_id', 'make_up_class_id']);
        });
        Schema::table('school_years', function (Blueprint $table) {
            $table->dropForeign('school_years_school_id_foreign');
        });
        Schema::table('school_locations', function (Blueprint $table) {
            $table->dropForeign('school_locations_school_id_foreign');
            $table->dropForeign('school_locations_location_id_foreign');
        });
        Schema::table('canceled_classes', function (Blueprint $table) {
            $table->dropForeign('canceled_classes_canceled_by_foreign');
            $table->dropForeign('canceled_classes_teacher_time_table_id_foreign');
        });
        Schema::table('make_up_classes', function (Blueprint $table) {
            $table->dropForeign('make_up_classes_canceled_class_id_foreign');
        });
        Schema::dropIfExists('teacher_request_dates');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('school_teachers');
        Schema::dropIfExists('special_work_days');
        Schema::dropIfExists('school_breaks');
        Schema::dropIfExists('extra_lesson_types');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('school_years');
        Schema::dropIfExists('school_locations');
        Schema::dropIfExists('canceled_classes');
        Schema::dropIfExists('make_up_classes');
    }

    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
            //
        });
    }
};
