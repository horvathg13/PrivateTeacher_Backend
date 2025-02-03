<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropColumns('termination_course_requests', ['message', 'status']);

    }

    public function down(): void
    {
        Schema::table('termination_course_requests', function (Blueprint $table) {
            //
        });
    }
};
