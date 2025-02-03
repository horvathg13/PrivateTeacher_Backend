<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('common_requests', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->enum('status',["UNDER_REVIEW", "ACCEPTED", "REJECTED"]);
            $table->morphs("requestable");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_requests');
    }
};
