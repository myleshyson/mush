<?php

namespace Myleshyson\Mush\Agents;

use Myleshyson\Mush\Agents\Concerns\HasWorkingDirectory;
use Myleshyson\Mush\Contracts\AgentInterface;
use Myleshyson\Mush\Contracts\AgentsSupport;
use Myleshyson\Mush\Contracts\CommandsSupport;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\McpSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;
use Myleshyson\Mush\Enums\Feature;

abstract class BaseAgent implements AgentInterface
{
    use HasWorkingDirectory;

    public function __construct(
        protected string $workingDirectory
    ) {}

    /**
     * Get the CLI option name for this agent.
     * Must be implemented by each agent class.
     */
    abstract public static function optionName(): string;

    abstract public function name(): string;

    /**
     * Get the paths that indicate this agent is present.
     * Override in subclasses to customize detection.
     *
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        $paths = [];

        if ($this->guidelines() !== null) {
            $paths[] = $this->guidelines()->path();
        }
        if ($this->mcp() !== null) {
            $paths[] = $this->mcp()->path();
        }

        return $paths;
    }

    /**
     * Detect if this agent is configured in the project.
     */
    public function detect(): bool
    {
        foreach ($this->detectionPaths() as $path) {
            $fullPath = $this->fullPath($path);
            if (file_exists($fullPath) || is_dir($fullPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this agent supports a specific feature.
     */
    public function supports(Feature $feature): bool
    {
        return match ($feature) {
            Feature::Guidelines => $this->guidelines() !== null,
            Feature::Skills => $this->skills() !== null,
            Feature::Mcp => $this->mcp() !== null,
            Feature::Agents => $this->agents() !== null,
            Feature::Commands => $this->commands() !== null,
        };
    }

    /**
     * Get guidelines support. Override in subclasses.
     */
    public function guidelines(): ?GuidelinesSupport
    {
        return null;
    }

    /**
     * Get skills support. Override in subclasses.
     */
    public function skills(): ?SkillsSupport
    {
        return null;
    }

    /**
     * Get MCP support. Override in subclasses.
     */
    public function mcp(): ?McpSupport
    {
        return null;
    }

    /**
     * Get agents support. Override in subclasses.
     */
    public function agents(): ?AgentsSupport
    {
        return null;
    }

    /**
     * Get commands support. Override in subclasses.
     */
    public function commands(): ?CommandsSupport
    {
        return null;
    }
}
