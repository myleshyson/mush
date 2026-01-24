<?php

namespace Myleshyson\Mush\Compilers;

use Symfony\Component\Yaml\Yaml;

class AgentsCompiler
{
    /**
     * Compile all agent markdown files from a directory.
     *
     * Agents are organized as markdown files directly in the directory.
     * e.g., agents/code-reviewer.md
     *
     * Returns an array of agent data with parsed frontmatter metadata.
     *
     * @param  string  $agentsDirectory  Path to the agents directory
     * @return array<string, array{name: string, description: string, content: string}> Map of agent-name => agent data
     */
    public function compile(string $agentsDirectory): array
    {
        $files = $this->getAgentFiles($agentsDirectory);

        if (empty($files)) {
            return [];
        }

        $agents = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false && trim($content) !== '') {
                // Use the filename (without extension) as the agent name
                $agentName = pathinfo($file, PATHINFO_FILENAME);
                $parsed = $this->parseAgentFile($content, $agentName);
                $agents[$agentName] = $parsed;
            }
        }

        // Sort alphabetically by agent name
        ksort($agents);

        return $agents;
    }

    /**
     * Parse an agent file to extract frontmatter metadata and content.
     *
     * @return array{name: string, description: string, content: string}
     */
    protected function parseAgentFile(string $content, string $fallbackName): array
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
     * Get all markdown files from the agents directory.
     *
     * @return string[]
     */
    protected function getAgentFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*.md');

        return $files !== false ? $files : [];
    }
}
