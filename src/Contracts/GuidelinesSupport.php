<?php

namespace Myleshyson\Mush\Contracts;

interface GuidelinesSupport
{
    /**
     * Get the path where guidelines should be written.
     */
    public function path(): string;

    /**
     * Write compiled guidelines content.
     */
    public function write(string $content): void;
}
