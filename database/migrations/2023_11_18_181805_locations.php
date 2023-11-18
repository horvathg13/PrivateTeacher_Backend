<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Locations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("locations", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country');
            $table->unsignedBigInteger('zip');
            $table->string('city');
            $table->string('street');
            $table->string('number');
            $table->unsignedBigInteger('floor')->nullable();
            $table->unsignedBigInteger('door')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
