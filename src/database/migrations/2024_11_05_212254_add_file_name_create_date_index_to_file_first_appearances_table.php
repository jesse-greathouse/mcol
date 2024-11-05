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
        Schema::table('file_first_appearances', function (Blueprint $table) {
            $table->index(['file_name', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_first_appearances', function (Blueprint $table) {
            $table->dropIndex('file_first_appearances_file_name_created_at_index');
        });
    }
};
