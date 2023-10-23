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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->index();
            $table->longText('topic');
            $table->integer('users')->index();
            $table->foreignId('channel_id')
                ->nullable(true)
                ->default(null);
            $table->foreignId('network_id');
            $table->unique(['network_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
