<?php

namespace Myleshyson\Mush\Agents\Claude\Features;

use Myleshyson\Mush\Agents\Concerns\HasWorkingDirectory;
use Myleshyson\Mush\Contracts\SkillsSupport;

class Skills implements SkillsSupport
{
    use HasWorkingDirectory;

    public function __construct(
        protected string $workingDirectory
    ) {}

    public function path(): string
    {
        return '.claude/skills/';
    }

    public function write(array $skills): void
    {
        $basePath = $this->fullPath($this->path());
        $this->ensureDirectoryExists($basePath);

        foreach ($skills as $skillName => $skillData) {
            $skillDir = rtrim($basePath, '/').'/'.$skillName;
            if (! is_dir($skillDir)) {
                mkdir($skillDir, 0755, true);
            }
            $skillPath = $skillDir.'/SKILL.md';

            $content = $this->reconstructContent($skillData);
            file_put_contents($skillPath, $content);
        }
    }

    /**
     * @param  array{name: string, description: string, content: string}  $skillData
     */
    protected function reconstructContent(array $skillData): string
    {
        $output = "---\n";
        $output .= "name: {$skillData['name']}\n";
        if ($skillData['description'] !== '') {
            $output .= "description: {$skillData['description']}\n";
        }
        $output .= "---\n\n";
        $output .= $skillData['content'];

        return $output;
    }
}
