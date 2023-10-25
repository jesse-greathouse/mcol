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
            $table->dropUnique('networks_channels_nicks_unique');
            $table->dropForeign('channel_id_foreign');
            $table->unique(['network_id', 'nick_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->foreignId('channel_id')->constrained();
            $table->unique(['network_id', 'channel_id', 'nick_id']);
        });
    }
};
