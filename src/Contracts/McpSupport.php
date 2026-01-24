<?php

namespace Myleshyson\Mush\Contracts;

interface McpSupport
{
    /**
     * Get the path where MCP config should be written.
     */
    public function path(): string;

    /**
     * Write MCP configuration.
     * This should merge with existing config if present.
     *
     * @param  array<string, mixed>  $servers  The MCP servers configuration
     */
    public function write(array $servers): void;
}
