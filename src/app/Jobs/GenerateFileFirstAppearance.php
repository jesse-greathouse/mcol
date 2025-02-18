<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable,
    Illuminate\Contracts\Queue\ShouldBeUnique,
    Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels;

use App\Models\FileFirstAppearance,
    App\Models\Packet;

/**
 * Job to update the file first appearance based on packet creation date.
 */
class GenerateFileFirstAppearance implements ShouldQueue, ShouldBeUnique
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
     *
     * @return void
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
