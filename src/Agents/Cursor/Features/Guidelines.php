<?php

namespace Myleshyson\Mush\Agents\Cursor\Features;

use Myleshyson\Mush\Agents\Concerns\HasWorkingDirectory;
use Myleshyson\Mush\Contracts\GuidelinesSupport;

class Guidelines implements GuidelinesSupport
{
    use HasWorkingDirectory;

    public function __construct(
        protected string $workingDirectory
    ) {}

    public function path(): string
    {
        return '.cursor/rules/mush.mdc';
    }

    public function write(string $content): void
    {
        $fullPath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($fullPath);

        // Wrap content with MDC frontmatter for Cursor rules format
        $mdcContent = "---\nalwaysApply: true\n---\n\n".$content;

        file_put_contents($fullPath, $mdcContent);
    }
}
