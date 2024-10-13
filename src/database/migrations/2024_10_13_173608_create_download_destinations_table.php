<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\DownloadDestination;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('download_destinations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('status')->index()->default(DownloadDestination::STATUS_WAITING);
            $table->string('destination_dir');
            $table->foreignId('download_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_destinations');
    }
};
