<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_course_teaching_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_course_id');
            $table->foreign('student_course_id')->references('id')->on('student_course');
            $table->enum('teaching_day',['MONDAY','TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY']);
            $table->time('from');
            $table->time('to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_course_teaching_days');
    }
};
