<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use Illuminate\Http\Request;
use Sajya\Server\Procedure;

use App\Exceptions\InvalidDownloadException,
    App\Jobs\RemoveCompletedDownload,
    App\Models\Download;

class RemoveCompletedProcedure extends Procedure
{
    /**
     * @var string
     */
    public static string $name = 'removeCompleted';

    /**
     * Execute the procedure.
     *
     * @param Request $request
     *
     * @return array|string|integer
     */
    public function request(Request $request)
    {
        $id = $request->input('download');
        $download = Download::where('id', $id)->first();
        if (null === $download) {
            throw new InvalidDownloadException("Download with id: $id was not found.");
        }

        RemoveCompletedDownload::dispatch($download);

        return [
            'download' => $download->toArray(),
        ];
    }
}
