<?php

namespace Myleshyson\Mush\Agents\OpenCode;

use Myleshyson\Mush\Agents\BaseAgent;
use Myleshyson\Mush\Agents\OpenCode\Features\Agents;
use Myleshyson\Mush\Agents\OpenCode\Features\Commands;
use Myleshyson\Mush\Agents\OpenCode\Features\Guidelines;
use Myleshyson\Mush\Agents\OpenCode\Features\Mcp;
use Myleshyson\Mush\Agents\OpenCode\Features\Skills;
use Myleshyson\Mush\Contracts\AgentsSupport;
use Myleshyson\Mush\Contracts\CommandsSupport;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\McpSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;

class OpenCode extends BaseAgent
{
    public static function optionName(): string
    {
        return 'opencode';
    }

    public function name(): string
    {
        return 'OpenCode';
    }

    /**
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        return [
            'AGENTS.md',
            'opencode.json',
            '.opencode/',
        ];
    }

    public function guidelines(): ?GuidelinesSupport
    {
        return new Guidelines($this->workingDirectory);
    }

    public function skills(): ?SkillsSupport
    {
        return new Skills($this->workingDirectory);
    }

    public function mcp(): ?McpSupport
    {
        return new Mcp($this->workingDirectory);
    }

    public function agents(): ?AgentsSupport
    {
        return new Agents($this->workingDirectory);
    }

    public function commands(): ?CommandsSupport
    {
        return new Commands($this->workingDirectory);
    }
}
