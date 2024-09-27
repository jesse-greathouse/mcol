<?php

namespace App\Packet\File;

final class FileExtension
{
    const AVI = 'avi';
    const DLL = 'dll';
    const DMG = 'dmg';
    const EPUB = 'epub';
    const EXE = 'exe';
    const FLAC = 'flac';
    const ISO = 'iso';
    const M4A = 'm4a';
    const M4B = 'm4b';
    const M4V = 'm4v';
    const MKV = 'mkv';
    const MOBI = 'mobi';
    const MP3 = 'mp3';
    const MP4 = 'mp4';
    const NSP = 'nsp';
    const PDF = 'pdf';
    const PKG = 'pkg';
    const RAR = 'rar';
    const TAR = 'tar';
    const TXT = 'txt';
    const WAV = 'wav';

    /**
     * Returns a list of a all the media types.
     *
     * @return array
     */
    public static function getFileExtensions(): array
    {
        return [
            self::AVI,
            self::DLL,
            self::DMG,
            self::EPUB,
            self::EXE,
            self::FLAC,
            self::ISO,
            self::M4A,
            self::M4B,
            self::M4V,
            self::MKV,
            self::MOBI,
            self::MP3,
            self::MP4,
            self::NSP,
            self::PDF,
            self::PKG,
            self::RAR,
            self::TAR,
            self::TXT,
            self::WAV,
        ];
    }
}
