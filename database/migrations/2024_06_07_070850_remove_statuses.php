<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveStatuses  extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('make_up_classes', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('make_up_classes', function (Blueprint $table) {
            $table->enum('status', ["ACCEPT", "REJECT"]);
        });

    }

    public function down(): void
    {

    }
};
