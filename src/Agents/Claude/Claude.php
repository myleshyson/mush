<?php

namespace Myleshyson\Mush\Agents\Claude;

use Myleshyson\Mush\Agents\BaseAgent;
use Myleshyson\Mush\Agents\Claude\Features\Agents;
use Myleshyson\Mush\Agents\Claude\Features\Commands;
use Myleshyson\Mush\Agents\Claude\Features\Guidelines;
use Myleshyson\Mush\Agents\Claude\Features\Mcp;
use Myleshyson\Mush\Agents\Claude\Features\Skills;
use Myleshyson\Mush\Contracts\AgentsSupport;
use Myleshyson\Mush\Contracts\CommandsSupport;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\McpSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;

class Claude extends BaseAgent
{
    public static function optionName(): string
    {
        return 'claude';
    }

    public function name(): string
    {
        return 'Claude Code';
    }

    /**
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        return [
            'CLAUDE.md',
            '.claude/CLAUDE.md',
            '.claude/',
            '.claude/mcp.json',
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
