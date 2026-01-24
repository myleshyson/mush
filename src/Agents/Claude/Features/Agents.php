<?php

namespace Myleshyson\Mush\Agents\Claude\Features;

use Myleshyson\Mush\Agents\Concerns\HasWorkingDirectory;
use Myleshyson\Mush\Contracts\AgentsSupport;

class Agents implements AgentsSupport
{
    use HasWorkingDirectory;

    public function __construct(
        protected string $workingDirectory
    ) {}

    public function path(): string
    {
        return '.claude/agents/';
    }

    public function write(array $agents): void
    {
        $basePath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($basePath);

        foreach ($agents as $agentName => $agentData) {
            $agentPath = rtrim($basePath, '/').'/'.$agentName.'.md';
            $content = $this->reconstructContent($agentData);
            file_put_contents($agentPath, $content);
        }
    }

    /**
     * @param  array{name: string, description: string, content: string}  $agentData
     */
    protected function reconstructContent(array $agentData): string
    {
        $output = "---\n";
        $output .= "name: {$agentData['name']}\n";
        if ($agentData['description'] !== '') {
            $output .= "description: {$agentData['description']}\n";
        }
        $output .= "---\n\n";
        $output .= $agentData['content'];

        return $output;
    }
}
