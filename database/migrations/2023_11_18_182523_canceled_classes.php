<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CanceledClasses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("canceled_classes", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_time_table_id');
            $table->foreign('teacher_time_table_id')->references('id')->on('teacher_time_tables');
            $table->unsignedBigInteger('canceled_by');
            $table->foreign('canceled_by')->references('id')->on('users');
            $table->timestamps();
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
