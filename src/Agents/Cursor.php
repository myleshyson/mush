<?php

namespace Myleshyson\Mush\Agents;

class Cursor extends BaseAgent
{
    public static function optionName(): string
    {
        return 'cursor';
    }

    public function name(): string
    {
        return 'Cursor';
    }

    /**
     * Cursor can be detected via multiple paths.
     *
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        return [
            '.cursorrules',  // Legacy format (still detected for migration)
            '.cursor/',
            '.cursor/rules/',
            '.cursor/mcp.json',
        ];
    }

    public function guidelinesPath(): string
    {
        return '.cursor/rules/mush.mdc';
    }

    public function writeGuidelines(string $content): void
    {
        $path = $this->fullPath($this->guidelinesPath());
        $this->ensureDirectoryExists($path);

        // Wrap content with MDC frontmatter for Cursor rules format
        $mdcContent = "---\nalwaysApply: true\n---\n\n".$content;

        file_put_contents($path, $mdcContent);
    }

    public function skillsPath(): string
    {
        return '.cursor/skills/';
    }

    public function mcpPath(): string
    {
        return '.cursor/mcp.json';
    }

    protected function transformMcpConfig(array $servers): array
    {
        // Cursor uses "mcpServers" key
        $mcpServers = [];

        foreach ($servers as $name => $config) {
            if (! is_array($config)) {
                continue;
            }

            $server = [];

            if (isset($config['command'])) {
                $command = $config['command'];
                $server['command'] = is_array($command) ? $command[0] : $command;
                if (is_array($command) && count($command) > 1) {
                    $server['args'] = array_slice($command, 1);
                }
                if (isset($config['env'])) {
                    $server['env'] = $config['env'];
                }
            } elseif (isset($config['url'])) {
                $server['url'] = $config['url'];
                if (isset($config['headers'])) {
                    $server['headers'] = $config['headers'];
                }
            }

            $mcpServers[$name] = $server;
        }

        // Use stdClass to ensure empty config serializes as {} not []
        return ['mcpServers' => empty($mcpServers) ? new \stdClass : $mcpServers];
    }

    protected function mergeMcpConfig(array $existing, array $new): array
    {
        $existingServers = isset($existing['mcpServers']) && is_array($existing['mcpServers']) ? $existing['mcpServers'] : [];
        $newServers = isset($new['mcpServers']) && is_array($new['mcpServers']) ? $new['mcpServers'] : [];

        if (! empty($existingServers) && ! empty($newServers)) {
            $new['mcpServers'] = array_replace_recursive($existingServers, $newServers);
        }

        return array_replace_recursive($existing, $new);
    }
}
