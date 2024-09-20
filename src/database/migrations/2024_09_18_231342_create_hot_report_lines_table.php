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
        Schema::create('hot_report_lines', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('hot_report_id')->nullable();
            $table->foreign('hot_report_id')->references('id')->on('hot_reports');
            $table->float('rating', 4, 1);
            $table->longText('term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hot_report_lines');
    }
};
