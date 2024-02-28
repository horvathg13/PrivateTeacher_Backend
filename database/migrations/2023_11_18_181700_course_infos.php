<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CourseInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("course_infos", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->unsignedBigInteger('student_limit');
            $table->unsignedBigInteger('minutes_lesson');
            $table->unsignedBigInteger('min_teaching_day');
            $table->boolean('double_time');
            $table->string('course_price_per_lesson');
            $table->unsignedBigInteger('status_id');
            $table->foreign('status')->references('id')->on('statuses');
            $table->unsignedBigInteger('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->unsignedBigInteger('school_year_id');
            $table->foreign('school_year_id')->references('id')->on('school_years');
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
