<?php

namespace Myleshyson\Mush\Agents\Codex;

use Myleshyson\Mush\Agents\BaseAgent;
use Myleshyson\Mush\Agents\Codex\Features\Guidelines;
use Myleshyson\Mush\Agents\Codex\Features\Skills;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;

class Codex extends BaseAgent
{
    public static function optionName(): string
    {
        return 'codex';
    }

    public function name(): string
    {
        return 'OpenAI Codex';
    }

    /**
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        return [
            'AGENTS.md',
            '.codex/',
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

    // Codex does not support project-local MCP config
    // mcp() returns null (inherited from BaseAgent)

    // Codex does not support custom agents
    // agents() returns null (inherited from BaseAgent)

    // Codex does not support custom slash commands
    // commands() returns null (inherited from BaseAgent)
}
