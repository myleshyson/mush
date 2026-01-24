<?php

namespace Myleshyson\Mush\Contracts;

interface CommandsSupport
{
    /**
     * Get the path where command definitions should be written.
     */
    public function path(): string;

    /**
     * Write command definitions.
     *
     * @param  array<string, array{name: string, description: string, content: string}>  $commands  Map of command-name => command data
     */
    public function write(array $commands): void;
}
