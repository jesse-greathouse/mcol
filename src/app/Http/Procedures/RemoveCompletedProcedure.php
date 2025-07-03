<?php

declare(strict_types=1);

namespace App\Http\Procedures;

use App\Exceptions\InvalidDownloadException;
use App\Jobs\RemoveCompletedDownload;
use App\Models\Download;
use Illuminate\Http\Request;
use Sajya\Server\Procedure;

/**
 * RemoveCompletedProcedure is responsible for handling the removal of completed downloads.
 */
class RemoveCompletedProcedure extends Procedure
{
    /**
     * The name of the procedure.
     */
    public static string $name = 'removeCompleted';

    /**
     * Execute the procedure to remove a completed download.
     *
     *
     * @return array<string, mixed> The response containing the download data.
     *
     * @throws InvalidDownloadException If the download is not found.
     */
    public function request(Request $request): array
    {
        $id = $request->input('download');

        // Fetch the download record efficiently using 'find'
        $download = Download::find($id);

        // If the download is not found, throw an exception
        if (! $download) {
            throw new InvalidDownloadException("Download with id: $id was not found.");
        }

        // Dispatch the job for removing completed download
        RemoveCompletedDownload::dispatch($download);

        // Return the download data in the required format
        return [
            'download' => $download->toArray(),
        ];
    }
}
