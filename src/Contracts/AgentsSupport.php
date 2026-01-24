<?php

namespace Myleshyson\Mush\Contracts;

interface AgentsSupport
{
    /**
     * Get the path where agent definitions should be written.
     */
    public function path(): string;

    /**
     * Write agent definitions.
     *
     * @param  array<string, array{name: string, description: string, content: string}>  $agents  Map of agent-name => agent data
     */
    public function write(array $agents): void;
}
