<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_infos', function (Blueprint $table) {
            $table->dropColumn('course_status');
            $table->enum('course_status', ['ACTIVE','SUSPENDED','DELETED', 'FINISHED'])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('course_infos', function (Blueprint $table) {
            //
        });
    }
};
