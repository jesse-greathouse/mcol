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
        Schema::create('instances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('pid')->nullable()->default(null);
            $table->boolean('enabled')->default(true);
            $table->string('status')->default('down');
            $table->longtext('log_uri');
            $table->foreignId('client_id')->constrained();
            $table->index('pid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instances');
    }
};
