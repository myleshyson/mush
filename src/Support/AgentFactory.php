<?php

namespace Myleshyson\Mush\Support;

use Myleshyson\Mush\Agents\Claude\Claude;
use Myleshyson\Mush\Agents\Codex\Codex;
use Myleshyson\Mush\Agents\Copilot\Copilot;
use Myleshyson\Mush\Agents\Cursor\Cursor;
use Myleshyson\Mush\Agents\Gemini\Gemini;
use Myleshyson\Mush\Agents\Junie\Junie;
use Myleshyson\Mush\Agents\OpenCode\OpenCode;
use Myleshyson\Mush\Contracts\AgentInterface;

class AgentFactory
{
    /**
     * List of all supported agent classes.
     * To add a new agent, simply add its class here.
     *
     * @var array<class-string<AgentInterface>>
     */
    private static array $agents = [
        Claude::class,
        OpenCode::class,
        Junie::class,
        Gemini::class,
        Copilot::class,
        Codex::class,
        Cursor::class,
    ];

    /**
     * Get all supported agent classes.
     *
     * @return array<class-string<AgentInterface>>
     */
    public static function agentClasses(): array
    {
        return self::$agents;
    }

    /**
     * Get a map of option names to agent classes.
     *
     * @return array<string, class-string<AgentInterface>>
     */
    public static function optionMap(): array
    {
        $map = [];
        foreach (self::$agents as $agentClass) {
            $map[$agentClass::optionName()] = $agentClass;
        }

        return $map;
    }

    /**
     * Get options for interactive prompt (option name => display name).
     *
     * @return array<string, string>
     */
    public static function promptOptions(string $workingDirectory): array
    {
        $options = [];
        foreach (self::$agents as $agentClass) {
            $instance = new $agentClass($workingDirectory);
            $options[$agentClass::optionName()] = $instance->name();
        }

        return $options;
    }

    /**
     * Create an agent instance from an option name.
     */
    public static function fromOptionName(string $optionName, string $workingDirectory): AgentInterface
    {
        $map = self::optionMap();

        if (! isset($map[$optionName])) {
            throw new \InvalidArgumentException("Unknown agent option: {$optionName}");
        }

        return new $map[$optionName]($workingDirectory);
    }

    /**
     * Create agent instances from an array of option names.
     *
     * @param  string[]  $optionNames
     * @return AgentInterface[]
     */
    public static function fromOptionNames(array $optionNames, string $workingDirectory): array
    {
        return array_map(
            fn (string $name) => self::fromOptionName($name, $workingDirectory),
            $optionNames
        );
    }

    /**
     * Create instances for all supported agents.
     *
     * @return AgentInterface[]
     */
    public static function all(string $workingDirectory): array
    {
        return array_map(
            fn (string $agentClass) => new $agentClass($workingDirectory),
            self::$agents
        );
    }

    /**
     * Detect which agents are configured in the project.
     *
     * @return AgentInterface[]
     */
    public static function detectAll(string $workingDirectory): array
    {
        $detected = [];

        foreach (self::$agents as $agentClass) {
            $instance = new $agentClass($workingDirectory);
            if ($instance->detect()) {
                $detected[] = $instance;
            }
        }

        return $detected;
    }
}
