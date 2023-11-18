<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TeacherCourseRequestsDates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create("teacher_course_request_dates", function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('teacher_course_request_id');
        $table->foreign('teacher_course_request_id')->references('id')->on('teacher_course_requests');
        $table->unsignedBigInteger('teaching_day_id');
        $table->foreign('teaching_day_id')->references('id')->on('teaching_days');
        $table->boolean('collapsed_lesson');
        $table->unsignedBigInteger('status');
        $table->foreign('status')->references('id')->on('statuses');
       });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
