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
        Schema::table('packets', function (Blueprint $table) {
            $table->string('resolution', length: 8)
                ->index()
                ->nullable();

            $table->string('extension', length: 8)
                ->index()
                ->nullable();

            $table->string('language', length: 16)
                ->index()
                ->default('');

            $table->boolean('is_hdr')
                ->index()
                ->default(false);

            $table->boolean('is_dolby_vision')
                ->index()
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packets', function (Blueprint $table) {
            $table->dropIndex('packets_resolution_index');
            $table->dropColumn('resolution');

            $table->dropIndex('packets_extension_index');
            $table->dropColumn('extension');

            $table->dropIndex('packets_language_index');
            $table->dropColumn('language');

            $table->dropIndex('packets_is_hdr_index');
            $table->dropColumn('is_hdr');

            $table->dropIndex('packets_is_dolby_vision_index');
            $table->dropColumn('is_dolby_vision');
        });
    }
};
