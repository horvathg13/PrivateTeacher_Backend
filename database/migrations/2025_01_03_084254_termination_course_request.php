<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('termination_course_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_course_id');
            $table->foreign('student_course_id')->references('id')->on('student_course');
            $table->date('from');
            $table->text('message')->nullable();
            $table->enum('status',["UNDER_REVIEW", "ACCEPTED", "REJECTED"]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('termination_course_requests');
    }
};
