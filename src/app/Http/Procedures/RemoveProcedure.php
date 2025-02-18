<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use Sajya\Server\Procedure;

use Illuminate\Http\Request;

use App\Exceptions\InvalidPacketException,
    App\Jobs\RemoveRequest,
    App\Models\Packet;

/**
 * Handles the removal procedure for a given packet.
 */
class RemoveProcedure extends Procedure
{
    /**
     * @var string The name of the procedure.
     */
    public static string $name = 'remove';

    /**
     * Executes the procedure to remove a packet.
     *
     * @param Request $request The request containing the packet ID.
     *
     * @return array|string|integer The result, which includes the packet data if found.
     * @throws InvalidPacketException If the packet is not found.
     */
    public function request(Request $request): array|string|int
    {
        // Retrieve the packet ID from the request
        $id = $request->input('packet');

        // Fetch the packet from the database
        $packet = Packet::find($id);

        // If packet is not found, throw an exception
        if (null === $packet) {
            throw new InvalidPacketException("Packet with id: $id was not found.");
        }

        // Dispatch the request removal job
        RemoveRequest::dispatch($packet);

        // Return the packet data
        return [
            'packet' => $packet->toArray(),
        ];
    }
}
