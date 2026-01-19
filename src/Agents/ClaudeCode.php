<?php

namespace Myleshyson\Fusion\Agents;

class ClaudeCode extends BaseAgent
{
    public function name(): string
    {
        return 'Claude Code';
    }

    public function guidelinesPath(): string
    {
        return '.claude/CLAUDE.md';
    }

    public function skillsPath(): string
    {
        return '.claude/skills/';
    }

    public function mcpPath(): string
    {
        return '.claude/mcp.json';
    }

    protected function transformMcpConfig(array $servers): array
    {
        $mcpServers = [];

        foreach ($servers as $name => $config) {
            $server = [];

            if (isset($config['command'])) {
                // Local server - split command array into command + args
                $command = $config['command'];
                $server['command'] = is_array($command) ? $command[0] : $command;
                if (is_array($command) && count($command) > 1) {
                    $server['args'] = array_slice($command, 1);
                }
                if (isset($config['env'])) {
                    $server['env'] = $config['env'];
                }
            } elseif (isset($config['url'])) {
                // Remote server
                $server['url'] = $config['url'];
                if (isset($config['headers'])) {
                    $server['headers'] = $config['headers'];
                }
            }

            $mcpServers[$name] = $server;
        }

        return ['mcpServers' => $mcpServers];
    }

    protected function mergeMcpConfig(array $existing, array $new): array
    {
        // Merge mcpServers specifically
        if (isset($existing['mcpServers']) && isset($new['mcpServers'])) {
            $new['mcpServers'] = array_replace_recursive($existing['mcpServers'], $new['mcpServers']);
        }

        return array_replace_recursive($existing, $new);
    }
}
