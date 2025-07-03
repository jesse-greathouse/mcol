<?php

namespace App\Packet\File;

/**
 * Class FileExtension
 *
 * This class defines constants for various file extensions and provides a method to retrieve a list of them.
 * It is used for identifying or validating file types based on their extensions.
 */
final class FileExtension
{
    // Define constants for various file extensions
    public const AVI = 'avi';  // Audio/Video Interleave

    public const DLL = 'dll';  // Dynamic-Link Library

    public const DMG = 'dmg';  // Disk Image (Apple macOS)

    public const EPUB = 'epub'; // Electronic Publication

    public const EXE = 'exe';  // Executable file

    public const FLAC = 'flac'; // Free Lossless Audio Codec

    public const ISO = 'iso';  // ISO Disk Image

    public const M4A = 'm4a'; // MPEG-4 Audio

    public const M4B = 'm4b'; // MPEG-4 Audio with Bookmarks (Audiobook)

    public const M4V = 'm4v'; // MPEG-4 Video

    public const MKV = 'mkv'; // Matroska Video

    public const MOBI = 'mobi'; // Mobipocket eBook

    public const MP3 = 'mp3'; // MPEG Audio Layer 3

    public const MP4 = 'mp4'; // MPEG-4 Video

    public const NSP = 'nsp'; // Nintendo Switch Package

    public const PDF = 'pdf'; // Portable Document Format

    public const PKG = 'pkg'; // Package File

    public const RAR = 'rar'; // Roshal Archive

    public const TAR = 'tar'; // Tape Archive

    public const TXT = 'txt'; // Plain Text

    public const WAV = 'wav'; // Waveform Audio File Format

    public const ZIP = 'zip'; // ZIP Compressed Archive

    /**
     * Returns a list of all the media types (file extensions).
     *
     * The returned list is an array containing common file extensions used for multimedia files and documents.
     * This method is designed for efficiency, returning a simple array of constants.
     *
     * @return string[] List of file extensions.
     */
    public static function getFileExtensions(): array
    {
        // Return an array of constants directly for computational efficiency
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
            self::ZIP,
        ];
    }
}
