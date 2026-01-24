<?php

namespace Myleshyson\Mush\Agents\Gemini;

use Myleshyson\Mush\Agents\BaseAgent;
use Myleshyson\Mush\Agents\Gemini\Features\Commands;
use Myleshyson\Mush\Agents\Gemini\Features\Guidelines;
use Myleshyson\Mush\Agents\Gemini\Features\Mcp;
use Myleshyson\Mush\Agents\Gemini\Features\Skills;
use Myleshyson\Mush\Contracts\CommandsSupport;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\McpSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;

class Gemini extends BaseAgent
{
    public static function optionName(): string
    {
        return 'gemini';
    }

    public function name(): string
    {
        return 'Gemini';
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

    // Gemini does not support custom agents
    // agents() returns null (inherited from BaseAgent)

    public function commands(): ?CommandsSupport
    {
        return new Commands($this->workingDirectory);
    }
}
