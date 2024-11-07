<?php

namespace App\Media;

use App\Exceptions\TransferFileDestinationPathException,
    App\Exceptions\TransferFileInvalidTmpDirException,
    App\Exceptions\TransferFileUriNotFoundException,
    App\Exceptions\TransferIllegalArchiveException,
    App\FileSystem,
    App\Packet\File\FileExtension;

class TransferManager
{
    use Filesystem;

    const TRANSFER_AGENT_DEFAULT = '\App\Media\Transfer\CopyFile';
    const TRANSFER_AGENT_RAR = '\App\Media\Transfer\Rar';
    const TRANSFER_AGENT_TAR = '\App\Media\Transfer\Tar';
    const TRANSFER_AGENT_ZIP = '\App\Media\Transfer\Zip';

    /**
     * The file to be transferred.
     *
     * @var string
     */
    protected $fileUri;

    /**
     * The the path of where to transfer the file to.
     *
     * @var string
     */
    protected $destinationPath;

    /**
     * An array to hold options for this operation.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * The the temporary path for working with archives.
     *
     * @var string
     */
    protected $tmpPath;

    /**
     * Directory of the file.
     *
     * @var string
     */
    protected $fileDirName;

    /**
     * Basename of the file.
     *
     * @var string
     */
    protected $fileBaseName;

    /**
     * File Extension of the file.
     *
     * @var string
     */
    protected $fileExtension;

    /**
     * File name of the file.
     *
     * @var string
     */
    protected $fileFileName;

    public function __construct(string $fileUri, string $destinationPath, array $options = [])
    {
        // No file, no transfer...
        // https://www.youtube.com/watch?v=iHSPf6x1Fdo
        if (!file_exists($fileUri)) {
            throw new TransferFileUriNotFoundException("File \"$fileUri\" could not be transferred because the file did not exist.");
        }

        $this->destinationPath = $destinationPath;
        $this->fileUri = $fileUri;
        $this->options = $options;

        [
            'dirname' => $this->fileDirName,
            'basename' => $this->fileBaseName,
            'extension' => $this->fileExtension,
            'filename' => $this->fileFileName
        ] = pathinfo($this->fileUri);

        if (!$this->preparePath($this->destinationPath)) {
            throw new TransferFileDestinationPathException("Unable to prepare destination path: \"{$this->destinationPath}\".");
        }
    }

    /**
     * Does the transfer.
     *
     * @return void
     */
    public function transfer(): void
    {
        if ($this->isArchive()) {
            $this->tmpPath = $this->getTemporaryDir();
            $transferAgent = $this->getArchiveTransferAgent();
        } else {
            $transferAgent = self::TRANSFER_AGENT_DEFAULT;
        }

        $agent = new $transferAgent($this, $this->getOptions());
        $agent->transfer();
        $agent->cleanup();

        if (!$this->keepFile() && $agent->isCompleted()) {
            unlink($this->getFileUri());
        }
    }

    /**
     * Find the transfer adapter for a file archive.
     *
     * @return string
     */
    public function getArchiveTransferAgent(): string
    {
        $ext = $this->getFileExtension();
        switch($ext) {
            case FileExtension::RAR:
                return self::TRANSFER_AGENT_RAR;
            case FileExtension::TAR:
                return self::TRANSFER_AGENT_TAR;
            case FileExtension::ZIP:
                return self::TRANSFER_AGENT_ZIP;
            default:
                throw new TransferIllegalArchiveException("Unable to locate Transfer Agent for archive: $ext");
                break;
        }
    }

    /**
     * Returns a list of extensions that are archives.
     *
     * @return array
     */
    public function getArchiveExtensions(): array
    {
        return [
            FileExtension::RAR,
            FileExtension::TAR,
            FileExtension::ZIP,
        ];
    }

    /**
     * Tells if the file to be transferred is an archive.
     *
     * @return boolean
     */
    public function isArchive(): bool
    {
        return (in_array($this->fileExtension, $this->getArchiveExtensions()));
    }

    /**
     * Returns true of the option to keep the original file has been set.
     *
     * @return boolean
     */
    public function keepFile(): bool
    {
        return (isset($this->options['keep_file']) && $this->options['keep_file']);
    }

    /**
     * Provides a temporary directory path for working with archives.
     *
     * @return string
     */
    protected function getTemporaryDir(): string
    {
        if (!isset($this->options['tmp_dir'])) {
            throw new TransferFileInvalidTmpDirException("Transfer Required a temporary directory, but a temporary root was not supplied.");
        }

        $tmpDir = $this->options['tmp_dir'] . $this->destinationPath;

        if (!$this->preparePath($tmpDir)) {
            throw new TransferFileInvalidTmpDirException("Unable to prepare temporary path: \"$tmpDir\".");
        }

        return $tmpDir;
    }

    /**
     * Get the file to be transferred.
     *
     * @return  string
     */
    public function getFileUri(): string
    {
        return $this->fileUri;
    }

    /**
     * Get the the path of where to transfer the file to.
     *
     * @return  string
     */
    public function getDestinationPath(): string
    {
        return $this->destinationPath;
    }

    /**
     * Get an array to hold options for this operation.
     *
     * @return  array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the the temporary path for working with archives.
     *
     * @return  string|null
     */
    public function getTmpPath(): string|null
    {
        return $this->tmpPath;
    }

    /**
     * Get directory of the file.
     *
     * @return  string
     */
    public function getFileDirName(): string
    {
        return $this->fileDirName;
    }

    /**
     * Get basename of the file.
     *
     * @return  string
     */
    public function getFileBaseName(): string
    {
        return $this->fileBaseName;
    }

    /**
     * Get file Extension of the file.
     *
     * @return  string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * Get file name of the file.
     *
     * @return  string
     */
    public function getFileFileName(): string
    {
        return $this->fileFileName;
    }
}
