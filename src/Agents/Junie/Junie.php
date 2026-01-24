<?php

namespace Myleshyson\Mush\Agents\Junie;

use Myleshyson\Mush\Agents\BaseAgent;
use Myleshyson\Mush\Agents\Junie\Features\Commands;
use Myleshyson\Mush\Agents\Junie\Features\Guidelines;
use Myleshyson\Mush\Agents\Junie\Features\Mcp;
use Myleshyson\Mush\Agents\Junie\Features\Skills;
use Myleshyson\Mush\Contracts\CommandsSupport;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\McpSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;

class Junie extends BaseAgent
{
    public static function optionName(): string
    {
        return 'junie';
    }

    public function name(): string
    {
        return 'Junie';
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

    // Junie does not support custom agents
    // agents() returns null (inherited from BaseAgent)

    public function commands(): ?CommandsSupport
    {
        return new Commands($this->workingDirectory);
    }
}
