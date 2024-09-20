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
        Schema::create('packet_search_results', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('packet_search_id')->nullable();
            $table->foreign('packet_search_id')->references('id')->on('packet_searches');
            $table->foreignId('packet_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packet_search_results');
    }
};
