<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('number')->index();
            $table->string('gets');
            $table->string('size');
            $table->string('file_name')->index();
            $table->foreignId('network_id')->constrained();
            $table->foreignId('channel_id')->constrained();
            $table->foreignId('bot_id')->constrained();
            $table->unique(['number', 'network_id', 'channel_id', 'bot_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packets');
    }
};
