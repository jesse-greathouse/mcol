<?php

namespace App\Media;

interface MediaTypeInterface
{
    public function toArray(): array;
    public function getMask(): string;
    public function map(): void;
}
