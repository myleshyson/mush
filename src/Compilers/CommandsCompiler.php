<?php

namespace Myleshyson\Mush\Compilers;

use Symfony\Component\Yaml\Yaml;

class CommandsCompiler
{
    /**
     * Compile all command markdown files from a directory.
     *
     * Commands are organized as markdown files directly in the directory.
     * e.g., commands/deploy.md
     *
     * Returns an array of command data with parsed frontmatter metadata.
     *
     * @param  string  $commandsDirectory  Path to the commands directory
     * @return array<string, array{name: string, description: string, content: string}> Map of command-name => command data
     */
    public function compile(string $commandsDirectory): array
    {
        $files = $this->getCommandFiles($commandsDirectory);

        if (empty($files)) {
            return [];
        }

        $commands = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false && trim($content) !== '') {
                // Use the filename (without extension) as the command name
                $commandName = pathinfo($file, PATHINFO_FILENAME);
                $parsed = $this->parseCommandFile($content, $commandName);
                $commands[$commandName] = $parsed;
            }
        }

        // Sort alphabetically by command name
        ksort($commands);

        return $commands;
    }

    /**
     * Parse a command file to extract frontmatter metadata and content.
     *
     * @return array{name: string, description: string, content: string}
     */
    protected function parseCommandFile(string $content, string $fallbackName): array
    {
        $name = $fallbackName;
        $description = '';
        $body = $content;

        // Check for YAML frontmatter (content between --- markers)
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $frontmatter = $matches[1];
            $body = $matches[2];

            try {
                $yaml = Yaml::parse($frontmatter);
                if (is_array($yaml)) {
                    if (isset($yaml['name']) && (is_string($yaml['name']) || is_numeric($yaml['name']))) {
                        $name = (string) $yaml['name'];
                    }
                    if (isset($yaml['description']) && (is_string($yaml['description']) || is_numeric($yaml['description']))) {
                        $description = (string) $yaml['description'];
                    }
                }
            } catch (\Exception $e) {
                // If YAML parsing fails, use defaults
            }
        }

        return [
            'name' => $name,
            'description' => $description,
            'content' => trim($body),
        ];
    }

    /**
     * Get all markdown files from the commands directory.
     *
     * @return string[]
     */
    protected function getCommandFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*.md');

        return $files !== false ? $files : [];
    }
}
