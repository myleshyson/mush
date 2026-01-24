<?php

namespace Myleshyson\Mush\Agents\OpenCode\Features;

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
        return 'opencode.json';
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
        $mcpConfig = [];

        foreach ($servers as $name => $config) {
            if (! is_array($config)) {
                continue;
            }

            $server = [];

            if (isset($config['command'])) {
                $server['type'] = 'local';
                $server['command'] = $config['command'];
                if (isset($config['env'])) {
                    $server['environment'] = $config['env'];
                }
            } elseif (isset($config['url'])) {
                $server['type'] = 'remote';
                $server['url'] = $config['url'];
                if (isset($config['headers'])) {
                    $server['headers'] = $config['headers'];
                }
            }

            $mcpConfig[$name] = $server;
        }

        return ['mcp' => empty($mcpConfig) ? new \stdClass : $mcpConfig];
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $new
     * @return array<string, mixed>
     */
    protected function merge(array $existing, array $new): array
    {
        $existingMcp = isset($existing['mcp']) && is_array($existing['mcp']) ? $existing['mcp'] : [];
        $newMcp = isset($new['mcp']) && is_array($new['mcp']) ? $new['mcp'] : [];

        if (! empty($existingMcp) && ! empty($newMcp)) {
            $new['mcp'] = array_replace_recursive($existingMcp, $newMcp);
        }

        return array_replace_recursive($existing, $new);
    }
}
