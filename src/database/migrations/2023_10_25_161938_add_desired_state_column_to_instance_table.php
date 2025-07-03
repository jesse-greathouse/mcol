<?php

use App\Models\Instance;
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
        Schema::table('instances', function (Blueprint $table) {
            $table->string('desired_status')->default(Instance::STATUS_UP);
            $table->string('status')->default(Instance::STATUS_DOWN)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropColumn('desired_status');
            $table->string('status')->default('down')->change();
        });
    }
};
