<?php

namespace App\Media;

use App\Exceptions\TransferFileDestinationPathException,
    App\Exceptions\TransferFileInvalidTmpDirException,
    App\Exceptions\TransferFileUriNotFoundException,
    App\Exceptions\TransferIllegalArchiveException,
    App\FileSystem,
    App\Packet\File\FileExtension;

/**
 * Class responsible for managing file transfers, including archives and temporary directories.
 *
 * This class handles transferring files, including special cases for archive files (RAR, TAR, ZIP),
 * and supports maintaining original files or deleting them based on options provided.
 */
class TransferManager
{
    use Filesystem;

    // Transfer agent constants
    const TRANSFER_AGENT_DEFAULT = '\App\Media\Transfer\CopyFile';
    const TRANSFER_AGENT_RAR = '\App\Media\Transfer\Rar';
    const TRANSFER_AGENT_TAR = '\App\Media\Transfer\Tar';
    const TRANSFER_AGENT_ZIP = '\App\Media\Transfer\Zip';

    /**
     * The file to be transferred.
     *
     * @var string
     */
    protected string $fileUri;

    /**
     * The directory where all files are initially downloaded.
     *
     * @var string
     */
    protected ?string $downloadDir = null;

    /**
     * The path of where to transfer the file to.
     *
     * @var string
     */
    protected string $destinationPath;

    /**
     * Options for this operation.
     *
     * @var array
     */
    protected array $options;

    /**
     * The temporary path for working with archives.
     *
     * @var string|null
     */
    protected ?string $tmpPath = null;

    /**
     * Directory of the file.
     *
     * @var string
     */
    protected string $fileDirName;

    /**
     * Basename of the file.
     *
     * @var string
     */
    protected string $fileBaseName;

    /**
     * File extension of the file.
     *
     * @var string
     */
    protected string $fileExtension;

    /**
     * File name of the file.
     *
     * @var string
     */
    protected string $fileFileName;

    /**
     * TransferManager constructor.
     *
     * Initializes the transfer manager with file URI, destination path, and options.
     *
     * @param string $fileUri        The file to transfer.
     * @param string $destinationPath The destination path for the file.
     * @param array  $options        Options for the transfer operation.
     * @throws TransferFileUriNotFoundException
     * @throws TransferFileDestinationPathException
     */
    public function __construct(string $fileUri, string $destinationPath, array $options = [])
    {
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
     * Executes the transfer.
     *
     * If the file is an archive, the corresponding transfer agent is used.
     * After the transfer, the file is optionally deleted based on the 'keep_file' option.
     *
     * @return void
     */
    public function transfer(): void
    {

        // Set up transferAgent
        $transferAgent = $this->isArchive() ?
            $this->getArchiveTransferAgent():
            self::TRANSFER_AGENT_DEFAULT;

        $agent = new $transferAgent($this, $this->options);
        $agent->transfer();
        $agent->cleanup();

        if (!$this->keepFile() && $agent->isCompleted()) {
            unlink($this->getFileUri());
        }
    }

    /**
     * Finds the appropriate transfer agent for an archive file based on its extension.
     *
     * @return string
     * @throws TransferIllegalArchiveException
     */
    public function getArchiveTransferAgent(): string
    {
        $ext = $this->getFileExtension();
        return match ($ext) {
            FileExtension::RAR => self::TRANSFER_AGENT_RAR,
            FileExtension::TAR => self::TRANSFER_AGENT_TAR,
            FileExtension::ZIP => self::TRANSFER_AGENT_ZIP,
            default => throw new TransferIllegalArchiveException("Unable to locate Transfer Agent for archive: $ext"),
        };
    }

    /**
     * Returns a list of extensions recognized as archives.
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
     * Determines if the file is an archive based on its extension.
     *
     * @return bool
     */
    public function isArchive(): bool
    {
        return in_array($this->fileExtension, $this->getArchiveExtensions(), true);
    }

    /**
     * Determines if the original file should be kept after transfer.
     *
     * @return bool
     */
    public function keepFile(): bool
    {
        return (bool) ($this->options['keep_file'] ?? false);
    }

    /**
     * Retrieves the URI of the file to be transferred.
     *
     * @return string
     */
    public function getFileUri(): string
    {
        return $this->fileUri;
    }

    /**
     * Retrieves the destination path of the file transfer.
     *
     * @return string
     */
    public function getDestinationPath(): string
    {
        return $this->destinationPath;
    }

    /**
     * Retrieves the options for this transfer operation.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Retrieves the temporary path used for archive transfers, if any.
     *
     * @return string|null
     */
    public function getTmpPath(): ?string
    {
        // Lazy creation of tmpPath
        if (null === $this->tmpPath) {
            if (!isset($this->options['tmp_dir'])) {
                throw new TransferFileInvalidTmpDirException("Transfer required a temporary directory, but none was supplied.");
            }

            $this->tmpPath = $this->options['tmp_dir'] . $this->destinationPath;

            if (!$this->preparePath($this->tmpPath)) {
                throw new TransferFileInvalidTmpDirException("Unable to prepare temporary path: \"{$this->tmpPath}\"");
            }
        }

        return $this->tmpPath;
    }

    /**
     * Retrieves the directory name of the file.
     *
     * @return string
     */
    public function getFileDirName(): string
    {
        return $this->fileDirName;
    }

    /**
     * Retrieves the basename of the file.
     *
     * @return string
     */
    public function getFileBaseName(): string
    {
        return $this->fileBaseName;
    }

    /**
     * Retrieves the file extension.
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * Retrieves the filename of the file.
     *
     * @return string
     */
    public function getFileFileName(): string
    {
        return $this->fileFileName;
    }

    /**
     * Get the directory where all files are initially downloaded.
     *
     * @return  string
     */
    public function getDownloadDir(): string
    {
        if (null === $this->downloadDir) {
            $this->downloadDir = env('DOWNLOAD_DIR');
        }

        return $this->downloadDir;
    }
}
