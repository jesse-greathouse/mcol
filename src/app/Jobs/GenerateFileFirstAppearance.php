<?php

namespace App\Jobs;

use App\Models\FileFirstAppearance;
use App\Models\Packet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to update the file first appearance based on packet creation date.
 */
class GenerateFileFirstAppearance implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 4800;

    /**
     * Handle the job of updating file first appearance for each packet.
     */
    public function handle(): void
    {
        // Optimize query to retrieve only necessary fields
        Packet::lazy()->each(function ($packet) {
            $file = FileFirstAppearance::firstOrCreate(
                ['file_name' => $packet->file_name],
                ['created_at' => $packet->created_at]
            );

            // Only update if the file creation date needs adjustment
            if ($file->created_at->gt($packet->created_at)) {
                $file->update(['created_at' => $packet->created_at]);
            }
        });
    }
}
