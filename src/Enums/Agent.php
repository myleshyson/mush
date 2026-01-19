<?php

namespace Myleshyson\Fusion\Enums;

enum Agent: string
{
    case ClaudeCode = 'claude-code';
    case OpenCode = 'opencode';
    case PhpStorm = 'phpstorm';
    case Gemini = 'gemini';
    case Copilot = 'copilot';
    case Codex = 'codex';
    case Cursor = 'cursor';

    /**
     * Get the display name for this agent.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::ClaudeCode => 'Claude Code',
            self::OpenCode => 'OpenCode',
            self::PhpStorm => 'PhpStorm (Junie)',
            self::Gemini => 'Gemini',
            self::Copilot => 'GitHub Copilot',
            self::Codex => 'OpenAI Codex',
            self::Cursor => 'Cursor',
        };
    }

    /**
     * Get all agent display names keyed by their config value.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->displayName();
        }

        return $options;
    }

    /**
     * Get all config values.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn (Agent $agent) => $agent->value, self::cases());
    }
}
