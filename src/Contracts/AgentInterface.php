<?php

namespace Myleshyson\Mush\Contracts;

use Myleshyson\Mush\Enums\Feature;

interface AgentInterface
{
    /**
     * Get the CLI option name for this agent (e.g., 'claude', 'cursor').
     * Used for `mush install --claude --cursor` etc.
     */
    public static function optionName(): string;

    /**
     * Get the display name for this agent.
     */
    public function name(): string;

    /**
     * Detect if this agent is configured in the project.
     * Returns true if any of the agent's config files exist.
     */
    public function detect(): bool;

    /**
     * Get the paths that indicate this agent is present.
     *
     * @return array<string>
     */
    public function detectionPaths(): array;

    /**
     * Check if this agent supports a specific feature.
     */
    public function supports(Feature $feature): bool;

    /**
     * Get guidelines support, or null if not supported.
     */
    public function guidelines(): ?GuidelinesSupport;

    /**
     * Get skills support, or null if not supported.
     */
    public function skills(): ?SkillsSupport;

    /**
     * Get MCP support, or null if not supported.
     */
    public function mcp(): ?McpSupport;

    /**
     * Get agents support, or null if not supported.
     */
    public function agents(): ?AgentsSupport;

    /**
     * Get commands support, or null if not supported.
     */
    public function commands(): ?CommandsSupport;
}
