<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class MediaStoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $linkTarget = ($this->isLink()) ? $this->getLinkTarget() : null;
        $uri = $this->getPath() . DIRECTORY_SEPARATOR . $this->getFilename();
        return [
            'uri'               => $uri,
            'basename'          => $this->getBasename(),
            'extension'         => $this->getExtension(),
            'modified'          => Carbon::createFromTimestamp($this->getMTime())->toDateTimeString(),
            'changed'           => Carbon::createFromTimestamp($this->getCTime())->toDateTimeString(),
            'owner'             => $this->getOwner(),
            'group'             => $this->getGroup(),
            'inode'             => $this->getInode(),
            'linkTarget'        => $linkTarget,
            'path'              => $this->getPath(),
            'path_name'         => $this->getPathName(),
            'perms'             => $this->getPerms(),
            'realpath'          => $this->getRealPath(),
            'size'              => $this->getSize(),
            'type'              => $this->getType(),
            'is_dir'            => $this->isDir(),
            'is_executable'     => $this->isExecutable(),
            'is_file'           => $this->isFile(),
            'is_link'           => $this->isLink(),
            'is_readable'       => $this->isReadable(),
            'is_writeable'      => $this->isWritable(),
        ];
    }
}
