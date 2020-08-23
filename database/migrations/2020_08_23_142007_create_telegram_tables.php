<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTelegramTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->string('link')->nullable();
            $table->bigInteger('id')->unique();
            $table->primary('id');
            $table->bigInteger('lastMessageID');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->increments('globalId');
            $table->bigInteger('id');
            $table->bigInteger('channelID');
            $table->text('text');
            $table->foreign('channelID')->references('id')->on('channels');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('channels');
    }
}
