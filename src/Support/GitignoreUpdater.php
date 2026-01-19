<?php

namespace Myleshyson\Mush\Support;

class GitignoreUpdater
{
    private const MUSH_START = '# MUSH GENERATED FILES';

    private const MUSH_END = '# END MUSH GENERATED FILES';

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
            $content = file_get_contents($gitignorePath);
            $existingContent = $content !== false ? $content : '';
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

        // Check if Mush section already exists
        if (str_contains($existingContent, self::MUSH_START)) {
            // Append to existing Mush section
            $content = $this->appendToMushSection($existingContent, $newPaths);
        } else {
            // Add new Mush section at the end
            $content = $existingContent !== '' ? rtrim($existingContent)."\n\n" : '';
            $content .= self::MUSH_START."\n";
            $content .= implode("\n", $newPaths)."\n";
            $content .= self::MUSH_END."\n";
        }

        file_put_contents($gitignorePath, $content);
    }

    /**
     * Append paths to the existing Mush section in .gitignore.
     *
     * @param  string[]  $newPaths
     */
    protected function appendToMushSection(string $content, array $newPaths): string
    {
        $lines = explode("\n", $content);
        $result = [];
        $inMushSection = false;
        $insertIndex = -1;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmedLine = trim($line);

            if ($trimmedLine === self::MUSH_START) {
                $inMushSection = true;
                $result[] = $line;

                continue;
            }

            if ($trimmedLine === self::MUSH_END) {
                // Insert new paths before the END marker
                foreach ($newPaths as $path) {
                    $result[] = $path;
                }
                $inMushSection = false;
                $result[] = $line;

                continue;
            }

            $result[] = $line;
        }

        // If we never found an END marker, add paths and the END marker
        if ($inMushSection) {
            foreach ($newPaths as $path) {
                $result[] = $path;
            }
            $result[] = self::MUSH_END;
        }

        return implode("\n", $result);
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

        // Remove leading / first
        $relativePath = ltrim($relativePath, '/');

        // Remove leading ./ (as a pair, not individually) to preserve paths like .claude/
        if (str_starts_with($relativePath, './')) {
            $relativePath = substr($relativePath, 2);
        }

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
