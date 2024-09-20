<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\FileFirstAppearance,
    App\Models\Packet;

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
     * Go through every packet
     * If there is a packet with created_at before the file first appearance
     * updated first appearance so it reflects the created date of the packet.
     */
    public function handle(): void
    {
        foreach (Packet::lazy() as $packet) {
            $file = FileFirstAppearance::firstOrCreate(
                ['file_name' => $packet->file_name]
            );

            if ($file->created_at->gt($packet->created_at)) {
                $file->created_at = $packet->created_at;
                $file->save();
            }
        }
    }
}
