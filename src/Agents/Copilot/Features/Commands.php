<?php

namespace Myleshyson\Mush\Agents\Copilot\Features;

use Myleshyson\Mush\Agents\Concerns\HasWorkingDirectory;
use Myleshyson\Mush\Contracts\CommandsSupport;

class Commands implements CommandsSupport
{
    use HasWorkingDirectory;

    public function __construct(
        protected string $workingDirectory
    ) {}

    public function path(): string
    {
        return '.github/prompts/';
    }

    public function write(array $commands): void
    {
        $basePath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($basePath);

        foreach ($commands as $commandName => $commandData) {
            // Copilot prompt files use .prompt.md extension
            $commandPath = rtrim($basePath, '/').'/'.$commandName.'.prompt.md';
            $content = $this->reconstructContent($commandData);
            file_put_contents($commandPath, $content);
        }
    }

    /**
     * @param  array{name: string, description: string, content: string}  $commandData
     */
    protected function reconstructContent(array $commandData): string
    {
        $output = "---\n";
        $output .= "agent: 'agent'\n";
        if ($commandData['description'] !== '') {
            $output .= "description: '{$commandData['description']}'\n";
        }
        $output .= "---\n\n";
        $output .= $commandData['content'];

        return $output;
    }
}
