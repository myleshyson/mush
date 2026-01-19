<?php

namespace Myleshyson\Fusion\Support;

use Myleshyson\Fusion\Agents\ClaudeCode;
use Myleshyson\Fusion\Agents\Codex;
use Myleshyson\Fusion\Agents\Copilot;
use Myleshyson\Fusion\Agents\Cursor;
use Myleshyson\Fusion\Agents\Gemini;
use Myleshyson\Fusion\Agents\OpenCode;
use Myleshyson\Fusion\Agents\PhpStorm;
use Myleshyson\Fusion\Contracts\AgentInterface;
use Myleshyson\Fusion\Enums\Agent;

class AgentFactory
{
    /**
     * Create an agent instance from an enum case.
     */
    public static function fromEnum(Agent $agent, string $workingDirectory): AgentInterface
    {
        return match ($agent) {
            Agent::ClaudeCode => new ClaudeCode($workingDirectory),
            Agent::OpenCode => new OpenCode($workingDirectory),
            Agent::PhpStorm => new PhpStorm($workingDirectory),
            Agent::Gemini => new Gemini($workingDirectory),
            Agent::Copilot => new Copilot($workingDirectory),
            Agent::Codex => new Codex($workingDirectory),
            Agent::Cursor => new Cursor($workingDirectory),
        };
    }

    /**
     * Create an agent instance from a config value string.
     */
    public static function fromString(string $agentValue, string $workingDirectory): AgentInterface
    {
        $agent = Agent::from($agentValue);

        return self::fromEnum($agent, $workingDirectory);
    }

    /**
     * Create all agent instances for the given config values.
     *
     * @param  string[]  $agentValues
     * @return AgentInterface[]
     */
    public static function fromArray(array $agentValues, string $workingDirectory): array
    {
        return array_map(
            fn (string $value) => self::fromString($value, $workingDirectory),
            $agentValues
        );
    }
}
