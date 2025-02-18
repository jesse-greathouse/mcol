<?php

namespace App\Http\Resources;

use Illuminate\Http\Request,
    Illuminate\Http\Resources\Json\JsonResource,
    Illuminate\Support\Carbon;

// Define DS constant for cross-platform compatibility if not already defined
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class MediaStoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $linkTarget = $this->isLink() ? $this->getLinkTarget() : null;
        $uri = $this->getPath() . DS . $this->getFilename();

        $timestamp = function ($time) {
            return Carbon::createFromTimestamp($time)->toDateTimeString();
        };

        return [
            'uri'              => $uri,
            'basename'         => $this->getBasename(),
            'extension'        => $this->getExtension(),
            'modified'         => $timestamp($this->getMTime()),
            'changed'          => $timestamp($this->getCTime()),
            'owner'            => $this->getOwner(),
            'group'            => $this->getGroup(),
            'inode'            => $this->getInode(),
            'link_target'      => $linkTarget,
            'path'             => $this->getPath(),
            'path_name'        => $this->getPathName(),
            'perms'            => $this->getPerms(),
            'realpath'         => $this->getRealPath(),
            'size'             => $this->getSize(),
            'type'             => $this->getType(),
            'is_dir'           => $this->isDir(),
            'is_executable'    => $this->isExecutable(),
            'is_file'          => $this->isFile(),
            'is_link'          => $this->isLink(),
            'is_readable'      => $this->isReadable(),
            'is_writeable'     => $this->isWritable(),
        ];
    }
}
