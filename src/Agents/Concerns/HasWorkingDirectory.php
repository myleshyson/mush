<?php

namespace Myleshyson\Mush\Agents\Concerns;

trait HasWorkingDirectory
{
    protected string $workingDirectory;

    protected function fullPath(string $relativePath): string
    {
        return rtrim($this->workingDirectory, '/').'/'.ltrim($relativePath, '/');
    }

    protected function ensureDirectoryExists(string $path): void
    {
        $dir = str_ends_with($path, '/') ? $path : dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
