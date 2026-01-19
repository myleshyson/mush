<?php

namespace Myleshyson\Fusion\Support;

class McpConfigReader
{
    /**
     * Read and merge MCP configuration from mcp.json and mcp.override.json.
     *
     * @return array<string, mixed>
     */
    public static function read(string $fusionPath): array
    {
        $baseConfig = self::readFile($fusionPath.'/mcp.json');
        $overrideConfig = self::readFile($fusionPath.'/mcp.override.json');

        // Override completely replaces matching servers
        return array_replace($baseConfig, $overrideConfig);
    }

    /**
     * Read a single MCP config file.
     *
     * @return array<string, mixed>
     */
    protected static function readFile(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }

        $config = json_decode($content, true);
        if (! is_array($config)) {
            return [];
        }

        /** @var array<string, mixed> */
        return $config['servers'] ?? [];
    }
}
