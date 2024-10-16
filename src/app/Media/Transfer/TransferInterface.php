<?php

namespace App\Media\Transfer;

interface TransferInterface
{
    public function transfer(string $uri = null): void;
    public function isCompleted(): bool;
    public function cleanup(): void;
}
