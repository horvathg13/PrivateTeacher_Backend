<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TeacherCourseRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create("teacher_course_requests", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("child_id");
            $table->foreign('child_id')->references('id')->on('children');
            $table->unsignedBigInteger("teacher_course_id");
            $table->foreign('teacher_course_id')->references('id')->on('course_infos');
            $table->unsignedBigInteger('number_of_lessons');
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->enum('status', ['ACCEPTED', 'REJECTED', 'UNDER_REVIEW']);
            $table->text('notice')->nullable();
            $table->timestamps();
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
