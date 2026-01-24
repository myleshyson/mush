<?php

namespace Myleshyson\Mush\Agents\Gemini\Features;

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
        return 'GEMINI.md';
    }

    public function write(string $content): void
    {
        $fullPath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($fullPath);
        file_put_contents($fullPath, $content);
    }
}
