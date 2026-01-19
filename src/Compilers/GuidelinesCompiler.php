<?php

namespace Myleshyson\Fusion\Compilers;

class GuidelinesCompiler
{
    /**
     * Compile all guideline markdown files from a directory into a single output string.
     *
     * @param  string  $guidelinesDirectory  Path to the guidelines directory
     * @return string Compiled guidelines content
     */
    public function compile(string $guidelinesDirectory): string
    {
        $files = $this->getMarkdownFiles($guidelinesDirectory);

        if (empty($files)) {
            return '';
        }

        $guidelines = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false && trim($content) !== '') {
                $guidelines[basename($file)] = trim($content);
            }
        }

        // Sort alphabetically by filename
        ksort($guidelines);

        return implode("\n\n", $guidelines);
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
