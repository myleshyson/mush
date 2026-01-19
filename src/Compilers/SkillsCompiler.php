<?php

namespace Myleshyson\Fusion\Compilers;

use Symfony\Component\Yaml\Yaml;

class SkillsCompiler
{
    /**
     * Compile all skill markdown files from a directory.
     *
     * Skills are organized as subdirectories containing a SKILL.md file.
     * e.g., skills/tailwind/SKILL.md
     *
     * Returns an array of skill data with parsed frontmatter metadata.
     *
     * @param  string  $skillsDirectory  Path to the skills directory
     * @return array<string, array{name: string, description: string, content: string}> Map of skill-name => skill data
     */
    public function compile(string $skillsDirectory): array
    {
        $files = $this->getSkillFiles($skillsDirectory);

        if (empty($files)) {
            return [];
        }

        $skills = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false && trim($content) !== '') {
                // Use the parent directory name as the skill name
                $skillName = basename(dirname($file));
                $parsed = $this->parseSkillFile($content, $skillName);
                $skills[$skillName] = $parsed;
            }
        }

        // Sort alphabetically by skill name
        ksort($skills);

        return $skills;
    }

    /**
     * Parse a skill file to extract frontmatter metadata and content.
     *
     * @return array{name: string, description: string, content: string}
     */
    protected function parseSkillFile(string $content, string $fallbackName): array
    {
        $name = $fallbackName;
        $description = '';
        $body = $content;

        // Check for YAML frontmatter (content between --- markers)
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            $frontmatter = $matches[1];
            $body = $matches[2];

            try {
                $yaml = Yaml::parse($frontmatter);
                if (is_array($yaml)) {
                    if (isset($yaml['name']) && (is_string($yaml['name']) || is_numeric($yaml['name']))) {
                        $name = (string) $yaml['name'];
                    }
                    if (isset($yaml['description']) && (is_string($yaml['description']) || is_numeric($yaml['description']))) {
                        $description = (string) $yaml['description'];
                    }
                }
            } catch (\Exception $e) {
                // If YAML parsing fails, use defaults
            }
        }

        return [
            'name' => $name,
            'description' => $description,
            'content' => trim($body),
        ];
    }

    /**
     * Get all SKILL.md files from subdirectories.
     *
     * @return string[]
     */
    protected function getSkillFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*/SKILL.md');

        return $files !== false ? $files : [];
    }
}
