<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_locations', function (Blueprint $table) {
            $table->dropPrimary(['school_id', 'location_id']);
        });
        Schema::table('school_locations', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::table('school_locations', function (Blueprint $table) {
            //
        });
    }
};
