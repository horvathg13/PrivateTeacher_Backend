<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign("messages_teacher_course_request_id_foreign");
            $table->dropColumn(['teacher_course_request_id']);
            $table->unsignedBigInteger("student_course_id")->nullable();
            $table->foreign("student_course_id")->references("id")->on("student_course");
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            //
        });
    }
};
