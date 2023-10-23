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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->boolean('enabled')->default(true);
            $table->foreignId('network_id')->constrained();
            $table->foreignId('channel_id')->constrained();
            $table->foreignId('nick_id')->constrained();
            $table->unique(['network_id', 'channel_id', 'nick_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
