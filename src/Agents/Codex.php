<?php

namespace Myleshyson\Fusion\Agents;

class Codex extends BaseAgent
{
    public function name(): string
    {
        return 'OpenAI Codex';
    }

    public function guidelinesPath(): string
    {
        return 'CODEX.md';
    }

    public function skillsPath(): string
    {
        return '.codex/skills/';
    }

    public function mcpPath(): string
    {
        return '.codex/mcp.json';
    }

    protected function transformMcpConfig(array $servers): array
    {
        // Codex uses same format as Claude Code
        $mcpServers = [];

        foreach ($servers as $name => $config) {
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

        return ['mcpServers' => $mcpServers];
    }

    protected function mergeMcpConfig(array $existing, array $new): array
    {
        if (isset($existing['mcpServers']) && isset($new['mcpServers'])) {
            $new['mcpServers'] = array_replace_recursive($existing['mcpServers'], $new['mcpServers']);
        }

        return array_replace_recursive($existing, $new);
    }
}
