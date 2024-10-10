<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use Illuminate\Http\Request;
use Sajya\Server\Procedure;

use App\Exceptions\InvalidPacketException,
    App\Jobs\RemoveRequest,
    App\Models\Packet;

class RemoveProcedure extends Procedure
{
    /**
     * @var string
     */
    public static string $name = 'remove';

    /**
     * Execute the procedure.
     *
     * @param Request $request
     *
     * @return array|string|integer
     */
    public function request(Request $request)
    {
        $id = $request->input('packet');
        $packet = Packet::where('id', $id)->first();
        if (null === $packet) {
            throw new InvalidPacketException("Packet with id: $id was not found.");
        }

        RemoveRequest::dispatch($packet);

        return [
            'packet' => $packet->toArray(),
        ];
    }
}
