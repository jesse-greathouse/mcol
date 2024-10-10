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
        Schema::create('download_histories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('file_name');
            $table->string('media_type', length: 16)->index();
            $table->string('file_uri')->index();
            $table->string('bot_nick')->index();
            $table->string('network_name')->index();
            $table->string('channel_name')->index();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->fullText('file_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_histories');
    }
};
