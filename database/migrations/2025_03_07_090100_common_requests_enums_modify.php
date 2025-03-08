<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('common_requests', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->enum('status',["UNDER_REVIEW", "ACCEPTED", "REJECTED", "CANCELLED"])->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('common_requests', function (Blueprint $table) {
            //
        });
    }
};
