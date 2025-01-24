<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_course', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_course_request_id')->nullable();
            $table->foreign('teacher_course_request_id')->references('id')->on('teacher_course_requests');
            $table->unsignedBigInteger('child_id');
            $table->foreign('child_id')->references('id')->on('children');
            $table->unsignedBigInteger('teacher_course_id');
            $table->foreign('teacher_course_id')->references('id')->on('course_infos');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_course');
    }
};
