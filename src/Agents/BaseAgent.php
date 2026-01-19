<?php

namespace Myleshyson\Fusion\Agents;

use Myleshyson\Fusion\Contracts\AgentInterface;

abstract class BaseAgent implements AgentInterface
{
    public function __construct(
        protected string $workingDirectory
    ) {}

    abstract public function name(): string;

    abstract public function guidelinesPath(): string;

    abstract public function skillsPath(): string;

    abstract public function mcpPath(): string;

    /**
     * Get the paths that indicate this agent is present.
     * Override in subclasses to customize detection.
     *
     * @return array<string>
     */
    public function detectionPaths(): array
    {
        return [
            $this->guidelinesPath(),
            $this->mcpPath(),
        ];
    }

    /**
     * Detect if this agent is configured in the project.
     */
    public function detect(): bool
    {
        foreach ($this->detectionPaths() as $path) {
            $fullPath = $this->fullPath($path);
            if (file_exists($fullPath) || is_dir($fullPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the full path for a relative path.
     */
    protected function fullPath(string $relativePath): string
    {
        return rtrim($this->workingDirectory, '/').'/'.ltrim($relativePath, '/');
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        $dir = str_ends_with($path, '/') ? $path : dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function writeGuidelines(string $content): void
    {
        $path = $this->fullPath($this->guidelinesPath());
        $this->ensureDirectoryExists($path);
        file_put_contents($path, $content);
    }

    public function writeSkills(array $skills): void
    {
        $basePath = $this->fullPath($this->skillsPath());
        $this->ensureDirectoryExists($basePath);

        foreach ($skills as $filename => $content) {
            $skillPath = rtrim($basePath, '/').'/'.$filename;
            file_put_contents($skillPath, $content);
        }
    }

    public function writeMcpConfig(array $servers): void
    {
        $path = $this->fullPath($this->mcpPath());
        $this->ensureDirectoryExists($path);

        // Load existing config if present
        $existingConfig = [];
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $existingConfig = json_decode($content, true) ?? [];
            }
        }

        // Transform servers to agent-specific format and merge
        $transformed = $this->transformMcpConfig($servers);
        $merged = $this->mergeMcpConfig($existingConfig, $transformed);

        file_put_contents($path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * Transform the source MCP config to the agent-specific format.
     *
     * @param  array<string, mixed>  $servers
     * @return array<string, mixed>
     */
    abstract protected function transformMcpConfig(array $servers): array;

    /**
     * Merge existing config with new MCP config.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $new
     * @return array<string, mixed>
     */
    protected function mergeMcpConfig(array $existing, array $new): array
    {
        return array_replace_recursive($existing, $new);
    }
}
