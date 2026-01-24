<?php

namespace Myleshyson\Mush\Agents\Copilot\Features;

use Myleshyson\Mush\Agents\Concerns\HasWorkingDirectory;
use Myleshyson\Mush\Contracts\McpSupport;

class Mcp implements McpSupport
{
    use HasWorkingDirectory;

    public function __construct(
        protected string $workingDirectory
    ) {}

    public function path(): string
    {
        return '.vscode/mcp.json';
    }

    public function write(array $servers): void
    {
        $path = $this->fullPath($this->path());
        $this->ensureDirectoryExists($path);

        $existingConfig = [];
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                $existingConfig = is_array($decoded) ? $decoded : [];
            }
        }

        $transformed = $this->transform($servers);
        $merged = $this->merge($existingConfig, $transformed);

        file_put_contents($path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * @param  array<string, mixed>  $servers
     * @return array<string, mixed>
     */
    protected function transform(array $servers): array
    {
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
                $server['type'] = 'http';
                $server['url'] = $config['url'];
                if (isset($config['headers'])) {
                    $server['headers'] = $config['headers'];
                }
            }

            $mcpServers[$name] = $server;
        }

        return ['servers' => empty($mcpServers) ? new \stdClass : $mcpServers];
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $new
     * @return array<string, mixed>
     */
    protected function merge(array $existing, array $new): array
    {
        $existingServers = isset($existing['servers']) && is_array($existing['servers'])
            ? $existing['servers']
            : [];
        $newServers = isset($new['servers']) && is_array($new['servers'])
            ? $new['servers']
            : [];

        if (! empty($existingServers) && ! empty($newServers)) {
            $new['servers'] = array_replace_recursive($existingServers, $newServers);
        }

        return array_replace_recursive($existing, $new);
    }
}
