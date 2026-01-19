<?php

namespace Myleshyson\Fusion\Contracts;

interface AgentInterface
{
    /**
     * Get the CLI option name for this agent (e.g., 'claude', 'cursor').
     * Used for `fusion install --claude --cursor` etc.
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
     * Get the path where guidelines should be written.
     */
    public function guidelinesPath(): string;

    /**
     * Get the path where skills should be written.
     */
    public function skillsPath(): string;

    /**
     * Get the path where MCP config should be written.
     * Return empty string if MCP is not supported for this agent.
     */
    public function mcpPath(): string;

    /**
     * Write compiled guidelines content to the agent's guidelines path.
     */
    public function writeGuidelines(string $content): void;

    /**
     * Write skills to the agent's skills path.
     *
     * @param  array<string, array{name: string, description: string, content: string}>  $skills  Map of skill-name => skill data
     */
    public function writeSkills(array $skills): void;

    /**
     * Write MCP configuration to the agent's MCP path.
     * This should merge with existing config if present.
     *
     * @param  array<string, mixed>  $servers  The MCP servers configuration
     */
    public function writeMcpConfig(array $servers): void;
}
