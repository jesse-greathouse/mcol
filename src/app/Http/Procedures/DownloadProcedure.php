<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use Illuminate\Http\Request;
use Sajya\Server\Procedure;

use App\Exceptions\InvalidPacketException,
    App\Jobs\DownloadRequest,
    App\Models\Packet;

class DownloadProcedure extends Procedure
{
    /**
     * @var string
     */
    public static string $name = 'download';

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

        DownloadRequest::dispatch($packet);

        return [
            'packet' => $packet->toArray(),
        ];
    }
}
