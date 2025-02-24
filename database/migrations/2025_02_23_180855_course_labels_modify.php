<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('language_id')->nullable();
            $table->foreign('language_id')->references('id')->on('languages');
        });
    }

    public function down(): void
    {
        Schema::table('course_labels', function (Blueprint $table) {
            //
        });
    }
};
