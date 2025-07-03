<?php

namespace App\Http\Controllers;

use App\Media\DownloadCard;
use App\Models\Download;
use Illuminate\Http\Request;

/**
 * Provides simple feedback mechanisms that shows the user the progress of the download.
 */
class DownloadProgressController
{
    public function show(Request $request)
    {
        $fileName = $request->query('fileName');
        $label = $request->query('label');

        if (! $fileName) {
            return response('fileName parameter is required.', 400);
        }

        $download = Download::where('file_name', $fileName)->first()
            ?? Download::where('file_name', str_replace('_', ' ', $fileName))->first();

        if (! $download) {
            return response("Download with name: $fileName does not exist.", 400);
        }

        $downloadCard = new DownloadCard($download, $label);

        return response($downloadCard->toSvg())->header('Content-Type', 'image/svg+xml');
    }
}
