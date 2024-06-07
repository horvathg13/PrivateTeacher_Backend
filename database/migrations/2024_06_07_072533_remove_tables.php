<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTables extends Migration {
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->dropIfExists();
        });
        Schema::table('payment_periods', function (Blueprint $table) {
            $table->dropIfExists();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('');
    }
};
