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
        Schema::table('downloads', function (Blueprint $table) {
            $table->bigInteger('file_size_bytes')->nullable();
            $table->bigInteger('progress_bytes')->nullable();
            $table->integer('queued_total')->nullable();
            $table->integer('queued_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropColumn('file_size_bytes');
            $table->dropColumn('progress_bytes');
            $table->dropColumn('queued_status');
            $table->dropColumn('queued_total');
        });
    }
};
