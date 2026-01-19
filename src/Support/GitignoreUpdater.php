<?php

namespace Myleshyson\Fusion\Support;

class GitignoreUpdater
{
    public function __construct(
        protected string $workingDirectory
    ) {}

    /**
     * Add paths to .gitignore if they're not already present.
     *
     * @param  string[]  $paths  Paths to add to .gitignore
     */
    public function addPaths(array $paths): void
    {
        $gitignorePath = rtrim($this->workingDirectory, '/').'/.gitignore';

        // Read existing .gitignore content
        $existingContent = '';
        $existingEntries = [];
        if (file_exists($gitignorePath)) {
            $existingContent = file_get_contents($gitignorePath);
            $existingEntries = array_filter(
                array_map('trim', explode("\n", $existingContent)),
                fn ($line) => $line !== '' && ! str_starts_with($line, '#')
            );
        }

        // Find paths that need to be added
        $newPaths = [];
        foreach ($paths as $path) {
            $normalizedPath = $this->normalizePath($path);
            if (! $this->isPathIgnored($normalizedPath, $existingEntries)) {
                $newPaths[] = $normalizedPath;
            }
        }

        if (empty($newPaths)) {
            return;
        }

        // Add new paths to .gitignore
        $content = rtrim($existingContent);
        if ($content !== '') {
            $content .= "\n\n";
        }
        $content .= "# Fusion generated files\n";
        $content .= implode("\n", $newPaths)."\n";

        file_put_contents($gitignorePath, $content);
    }

    /**
     * Normalize a path for .gitignore (remove leading ./ and add leading /)
     */
    protected function normalizePath(string $path): string
    {
        // Remove working directory prefix if present
        $relativePath = str_starts_with($path, $this->workingDirectory)
            ? substr($path, strlen($this->workingDirectory))
            : $path;

        // Remove leading ./ or /
        $relativePath = ltrim($relativePath, './');

        // Add leading / for .gitignore (means root of repo)
        return '/'.$relativePath;
    }

    /**
     * Check if a path is already covered by existing .gitignore entries.
     *
     * @param  string[]  $existingEntries
     */
    protected function isPathIgnored(string $path, array $existingEntries): bool
    {
        foreach ($existingEntries as $entry) {
            // Exact match
            if ($entry === $path || $entry === ltrim($path, '/')) {
                return true;
            }

            // Check if the path is under an ignored directory
            $entryDir = rtrim($entry, '/').'/';
            $pathDir = rtrim($path, '/').'/';
            if (str_starts_with($pathDir, $entryDir) || str_starts_with($pathDir, '/'.ltrim($entryDir, '/'))) {
                return true;
            }
        }

        return false;
    }
}
