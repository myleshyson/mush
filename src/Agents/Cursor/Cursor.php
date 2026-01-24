<?php

namespace Myleshyson\Mush\Agents\Cursor;

use Myleshyson\Mush\Agents\BaseAgent;
use Myleshyson\Mush\Agents\Cursor\Features\Commands;
use Myleshyson\Mush\Agents\Cursor\Features\Guidelines;
use Myleshyson\Mush\Agents\Cursor\Features\Mcp;
use Myleshyson\Mush\Agents\Cursor\Features\Skills;
use Myleshyson\Mush\Contracts\CommandsSupport;
use Myleshyson\Mush\Contracts\GuidelinesSupport;
use Myleshyson\Mush\Contracts\McpSupport;
use Myleshyson\Mush\Contracts\SkillsSupport;

class Cursor extends BaseAgent
{
    public static function optionName(): string
    {
        return 'cursor';
    }

    public function name(): string
    {
        return 'Cursor';
    }

    /**
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        return [
            '.cursorrules',  // Legacy format (still detected for migration)
            '.cursor/',
            '.cursor/rules/',
            '.cursor/mcp.json',
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

    // Cursor does not support custom agents (custom modes were deprecated in 2.1)
    // agents() returns null (inherited from BaseAgent)

    public function commands(): ?CommandsSupport
    {
        return new Commands($this->workingDirectory);
    }
}
