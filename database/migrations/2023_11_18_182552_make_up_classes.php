<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUpClasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("make_up_classes", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('canceled_class_id');
            $table->foreign('canceled_class_id')->references('id')->on('canceled_classes');
            $table->dateTime('new_start');
            $table->dateTime('new_end');
            $table->unsignedBigInteger('status');
            $table->foreign('status')->references('id')->on('statuses');
    
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
