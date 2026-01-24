<?php

namespace Myleshyson\Mush\Agents\Junie\Features;

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
        return '.junie/commands/';
    }

    public function write(array $commands): void
    {
        $basePath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($basePath);

        foreach ($commands as $commandName => $commandData) {
            $commandPath = rtrim($basePath, '/').'/'.$commandName.'.md';
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
        if ($commandData['description'] !== '') {
            $output .= "description: {$commandData['description']}\n";
        }
        $output .= "---\n\n";
        $output .= $commandData['content'];

        return $output;
    }
}
