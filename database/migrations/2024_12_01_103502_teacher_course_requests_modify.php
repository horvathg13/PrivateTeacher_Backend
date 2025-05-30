<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teacher_course_requests', function (Blueprint $table) {
            $table->text('teacher_justification')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_course_requests', function (Blueprint $table) {
            //
        });
    }
};
