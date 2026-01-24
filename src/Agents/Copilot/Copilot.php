<?php

namespace Myleshyson\Mush\Agents\Copilot;

use Myleshyson\Mush\Agents\BaseAgent;
use Myleshyson\Mush\Agents\Copilot\Features\Agents;
use Myleshyson\Mush\Agents\Copilot\Features\Commands;
use Myleshyson\Mush\Agents\Copilot\Features\Guidelines;
use Myleshyson\Mush\Agents\Copilot\Features\Mcp;
use Myleshyson\Mush\Agents\Copilot\Features\Skills;
use Myleshyson\Mush\Contracts\AgentsSupport;
use Myleshyson\Mush\Contracts\CommandsSupport;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\McpSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;

class Copilot extends BaseAgent
{
    public static function optionName(): string
    {
        return 'copilot';
    }

    public function name(): string
    {
        return 'GitHub Copilot';
    }

    /**
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        return [
            '.github/copilot-instructions.md',
            '.vscode/mcp.json',
            '.github/',
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
