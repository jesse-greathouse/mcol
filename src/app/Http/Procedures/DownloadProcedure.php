<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use App\Exceptions\InvalidPacketException;
use App\Jobs\DownloadRequest;
use App\Models\Packet;
use Illuminate\Http\Request;
use Sajya\Server\Procedure;

class DownloadProcedure extends Procedure
{
    /** @var string The procedure's name. */
    public static string $name = 'download';

    /**
     * Execute the procedure to handle download requests.
     *
     * @param  Request  $request  The HTTP request containing the packet ID.
     * @return array<string, mixed> The packet data or an error message.
     *
     * @throws InvalidPacketException If the packet is not found in the database.
     */
    public function request(Request $request): array|string|int
    {
        $id = $request->input('packet');
        $packet = Packet::find($id);

        // Early exit for readability and efficiency
        if ($packet === null) {
            throw new InvalidPacketException("Packet with id: $id was not found.");
        }

        // Dispatch the job asynchronously
        DownloadRequest::dispatch($packet);

        return [
            'packet' => $packet->toArray(), // Use toArray() for returning packet data
        ];
    }
}
