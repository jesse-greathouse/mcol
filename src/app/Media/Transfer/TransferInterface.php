<?php

namespace App\Media\Transfer;

/**
 * Interface for transferring media with methods for completion check and cleanup.
 *
 * Defines the core methods for initiating transfers, checking their completion status,
 * and performing necessary cleanup after a transfer is done.
 */
interface TransferInterface
{
    /**
     * Initiates the transfer process.
     *
     * @param  string|null  $uri  The URI to transfer data from or to. Defaults to null.
     */
    public function transfer(?string $uri = null): void;

    /**
     * Checks if the transfer process has been completed.
     *
     * @return bool True if transfer is complete, otherwise false.
     */
    public function isCompleted(): bool;

    /**
     * Performs cleanup actions after transfer completion.
     *
     * This method may include tasks like closing file streams or removing temporary files.
     */
    public function cleanup(): void;
}
