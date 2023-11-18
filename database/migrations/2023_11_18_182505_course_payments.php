<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CoursePayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("course_payment", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_course_id');
            $table->foreign('teacher_course_id')->references('id')->on('teachers_course');
            $table->unsignedBigInteger('child_id');
            $table->foreign('child_id')->references('id')->on('children');
            $table->boolean('paid');
            $table->date('first_date');
            $table->date('last_date');
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
