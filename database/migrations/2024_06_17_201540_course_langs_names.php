<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_langs_names', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->foreign('course_id')->references('id')->on('course_infos')->onDelete('cascade');
            $table->string('lang');
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_langs_names');
    }
};
