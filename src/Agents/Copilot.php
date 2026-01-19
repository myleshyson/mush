<?php

namespace Myleshyson\Fusion\Agents;

class Copilot extends BaseAgent
{
    public function name(): string
    {
        return 'GitHub Copilot';
    }

    public function guidelinesPath(): string
    {
        return '.github/copilot-instructions.md';
    }

    public function skillsPath(): string
    {
        return '.github/skills/';
    }

    public function mcpPath(): string
    {
        return '.vscode/mcp.json';
    }

    protected function transformMcpConfig(array $servers): array
    {
        // VS Code/Copilot uses "servers" key with "type": "http" for remote
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
                $server['type'] = 'http';
                $server['url'] = $config['url'];
                if (isset($config['headers'])) {
                    $server['headers'] = $config['headers'];
                }
            }

            $mcpServers[$name] = $server;
        }

        return ['servers' => $mcpServers];
    }

    protected function mergeMcpConfig(array $existing, array $new): array
    {
        if (isset($existing['servers']) && isset($new['servers'])) {
            $new['servers'] = array_replace_recursive($existing['servers'], $new['servers']);
        }

        return array_replace_recursive($existing, $new);
    }
}
