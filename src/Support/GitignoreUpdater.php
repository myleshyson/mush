<?php

namespace Myleshyson\Mush\Support;

class GitignoreUpdater
{
    private const FUSION_HEADER = '# Fusion generated files';

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

        // Check if Fusion section already exists
        if (str_contains($existingContent, self::FUSION_HEADER)) {
            // Append to existing Fusion section
            $content = $this->appendToFusionSection($existingContent, $newPaths);
        } else {
            // Add new Fusion section at the end
            $content = $existingContent !== '' ? rtrim($existingContent)."\n\n" : '';
            $content .= self::FUSION_HEADER."\n";
            $content .= implode("\n", $newPaths)."\n";
        }

        file_put_contents($gitignorePath, $content);
    }

    /**
     * Append paths to the existing Fusion section in .gitignore.
     *
     * @param  string[]  $newPaths
     */
    protected function appendToFusionSection(string $content, array $newPaths): string
    {
        $lines = explode("\n", $content);
        $result = [];
        $inFusionSection = false;
        $fusionSectionEnd = -1;

        // Find the last Fusion section and its end
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            if (trim($line) === self::FUSION_HEADER) {
                $inFusionSection = true;
                $fusionSectionEnd = $i;
            } elseif ($inFusionSection) {
                // We're in a Fusion section - track the last non-empty line
                if (trim($line) !== '' && ! str_starts_with(trim($line), '#')) {
                    $fusionSectionEnd = $i;
                } elseif (str_starts_with(trim($line), '#') && trim($line) !== self::FUSION_HEADER) {
                    // Hit a different comment section, Fusion section ended
                    $inFusionSection = false;
                }
            }
        }

        // Insert new paths after the last Fusion section entry
        foreach ($lines as $i => $line) {
            $result[] = $line;
            if ($i === $fusionSectionEnd) {
                foreach ($newPaths as $path) {
                    $result[] = $path;
                }
            }
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
