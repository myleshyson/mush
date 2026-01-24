<?php

namespace Myleshyson\Mush\Agents\Gemini\Features;

use Myleshyson\Mush\Agents\Concerns\HasWorkingDirectory;
use Myleshyson\Mush\Contracts\CommandsSupport;

/**
 * Gemini commands use TOML format in VS Code.
 */
class Commands implements CommandsSupport
{
    use HasWorkingDirectory;

    public function __construct(
        protected string $workingDirectory
    ) {}

    public function path(): string
    {
        return '.gemini/commands/';
    }

    public function write(array $commands): void
    {
        $basePath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($basePath);

        foreach ($commands as $commandName => $commandData) {
            // Gemini commands use TOML format
            $commandPath = rtrim($basePath, '/').'/'.$commandName.'.toml';
            $content = $this->reconstructContent($commandData);
            file_put_contents($commandPath, $content);
        }
    }

    /**
     * @param  array{name: string, description: string, content: string}  $commandData
     */
    protected function reconstructContent(array $commandData): string
    {
        $output = '';
        if ($commandData['description'] !== '') {
            $output .= "description = \"{$this->escapeToml($commandData['description'])}\"\n";
        }
        $output .= "prompt = \"\"\"\n{$commandData['content']}\n\"\"\"\n";

        return $output;
    }

    protected function escapeToml(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
