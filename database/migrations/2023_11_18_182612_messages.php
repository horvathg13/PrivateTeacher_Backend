<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Messages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("messages", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_course_request_id')->nullable();
            $table->foreign('teacher_course_request_id')->references('id')->on('teacher_course_requests');
            $table->unsignedBigInteger('canceled_class_id')->nullable();
            $table->foreign('canceled_class_id')->references('id')->on('canceled_classes');
            $table->unsignedBigInteger('make_up_class_id')->nullable();
            $table->foreign('make_up_class_id')->references('id')->on('make_up_classes');
            $table->unsignedBigInteger('sender_id');
            $table->foreign('sender_id')->references('id')->on('users');
            $table->unsignedBigInteger('receiver_id');
            $table->foreign('receiver_id')->references('id')->on('users');
            $table->text('message');
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
