<?php

namespace Myleshyson\Mush\Agents\Cursor\Features;

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
        return '.cursor/commands/';
    }

    public function write(array $commands): void
    {
        $basePath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($basePath);

        foreach ($commands as $commandName => $commandData) {
            // Cursor commands are plain markdown files (no frontmatter required)
            $commandPath = rtrim($basePath, '/').'/'.$commandName.'.md';
            file_put_contents($commandPath, $commandData['content']);
        }
    }
}
