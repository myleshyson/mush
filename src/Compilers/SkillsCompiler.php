<?php

namespace Myleshyson\Fusion\Compilers;

class SkillsCompiler
{
    /**
     * Compile all skill markdown files from a directory.
     *
     * @param  string  $skillsDirectory  Path to the skills directory
     * @return array<string, string> Map of filename => content
     */
    public function compile(string $skillsDirectory): array
    {
        $files = $this->getMarkdownFiles($skillsDirectory);

        if (empty($files)) {
            return [];
        }

        $skills = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false && trim($content) !== '') {
                $skills[basename($file)] = trim($content);
            }
        }

        // Sort alphabetically by filename
        ksort($skills);

        return $skills;
    }

    /**
     * Get all markdown files from a directory, sorted alphabetically.
     *
     * @return string[]
     */
    protected function getMarkdownFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = glob(rtrim($directory, '/').'/*.md');

        return $files !== false ? $files : [];
    }
}
