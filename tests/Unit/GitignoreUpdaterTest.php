<?php

use Myleshyson\Mush\Support\GitignoreUpdater;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../artifacts/gitignore';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('GitignoreUpdater', function () {
    it('creates .gitignore if it does not exist', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        expect("{$this->artifactPath}/.gitignore")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('/.claude/CLAUDE.md');
    });

    it('appends to existing .gitignore', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", "vendor/\nnode_modules/\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('vendor/');
        expect($content)->toContain('node_modules/');
        expect($content)->toContain('/.claude/CLAUDE.md');
    });

    it('adds mush section markers', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('# MUSH GENERATED FILES');
        expect($content)->toContain('# END MUSH GENERATED FILES');
    });

    it('places paths between mush section markers', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md', 'AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Verify structure: START, paths, END
        $lines = explode("\n", $content);
        $startIndex = array_search('# MUSH GENERATED FILES', $lines);
        $endIndex = array_search('# END MUSH GENERATED FILES', $lines);

        expect($startIndex)->toBeLessThan($endIndex);

        // Paths should be between markers
        $claudeIndex = array_search('/.claude/CLAUDE.md', $lines);
        $agentsIndex = array_search('/AGENTS.md', $lines);

        expect($claudeIndex)->toBeGreaterThan($startIndex);
        expect($claudeIndex)->toBeLessThan($endIndex);
        expect($agentsIndex)->toBeGreaterThan($startIndex);
        expect($agentsIndex)->toBeLessThan($endIndex);
    });

    it('does not add duplicate paths', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", "/.claude/CLAUDE.md\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should only have one occurrence
        expect(substr_count($content, '.claude/CLAUDE.md'))->toBe(1);
    });

    it('does not add paths already covered by directory patterns', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", "/.claude/\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md', '.claude/mcp.json']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should not add the files since .claude/ covers them
        expect($content)->not->toContain('/.claude/CLAUDE.md');
        expect($content)->not->toContain('/.claude/mcp.json');
    });

    it('normalizes paths with leading ./', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['./AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('/AGENTS.md');
        expect($content)->not->toContain('./');
    });

    it('handles multiple paths at once', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths([
            'AGENTS.md',
            '.claude/CLAUDE.md',
            '.cursor/mcp.json',
        ]);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('/AGENTS.md');
        expect($content)->toContain('/.claude/CLAUDE.md');
        expect($content)->toContain('/.cursor/mcp.json');
    });

    it('does not modify file when all paths already exist', function () {
        $existingContent = "/.claude/CLAUDE.md\n/AGENTS.md\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md', 'AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toBe($existingContent);
    });

    it('handles paths without leading slash in existing gitignore', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", ".claude/CLAUDE.md\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should recognize it's already there (without leading slash)
        expect(substr_count($content, 'CLAUDE.md'))->toBe(1);
    });

    it('handles empty path array', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths([]);

        expect("{$this->artifactPath}/.gitignore")->not->toBeFile();
    });

    it('handles existing gitignore with comments', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", "# Dependencies\nvendor/\n# Build\nbuild/\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('# Dependencies');
        expect($content)->toContain('vendor/');
        expect($content)->toContain('/AGENTS.md');
    });

    it('appends to existing Mush section instead of creating new one', function () {
        $existingContent = "vendor/\n\n# MUSH GENERATED FILES\n/AGENTS.md\n/.claude/CLAUDE.md\n# END MUSH GENERATED FILES\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json', 'GEMINI.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Should only have one set of markers
        expect(substr_count($content, '# MUSH GENERATED FILES'))->toBe(1);
        expect(substr_count($content, '# END MUSH GENERATED FILES'))->toBe(1);

        // Should contain all paths
        expect($content)->toContain('/AGENTS.md');
        expect($content)->toContain('/.claude/CLAUDE.md');
        expect($content)->toContain('/.cursor/mcp.json');
        expect($content)->toContain('/GEMINI.md');
    });

    it('appends to Mush section even when other sections follow', function () {
        $existingContent = "# MUSH GENERATED FILES\n/AGENTS.md\n# END MUSH GENERATED FILES\n\n# Other stuff\n/build/\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Should only have one set of markers
        expect(substr_count($content, '# MUSH GENERATED FILES'))->toBe(1);
        expect(substr_count($content, '# END MUSH GENERATED FILES'))->toBe(1);

        // New path should be added within Mush section
        expect($content)->toContain('/.cursor/mcp.json');

        // Other section should still be there
        expect($content)->toContain('# Other stuff');
        expect($content)->toContain('/build/');
    });

    it('inserts new paths before END marker', function () {
        $existingContent = "# MUSH GENERATED FILES\n/AGENTS.md\n# END MUSH GENERATED FILES\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        $lines = explode("\n", $content);

        $cursorIndex = array_search('/.cursor/mcp.json', $lines);
        $endIndex = array_search('# END MUSH GENERATED FILES', $lines);

        expect($cursorIndex)->toBeLessThan($endIndex);
    });

    it('adds END marker if missing from existing section', function () {
        $existingContent = "# MUSH GENERATED FILES\n/AGENTS.md\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        expect($content)->toContain('# END MUSH GENERATED FILES');
        expect($content)->toContain('/.cursor/mcp.json');
    });

    it('matches paths with leading slash in existing entries', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", "/.claude/CLAUDE.md\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should recognize exact match with leading slash
        expect(substr_count($content, 'CLAUDE.md'))->toBe(1);
    });

    it('matches paths without leading slash in existing entries', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", ".claude/CLAUDE.md\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should recognize exact match without leading slash (ltrim branch)
        expect(substr_count($content, 'CLAUDE.md'))->toBe(1);
    });

    it('detects paths under ignored directory with leading slash', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", "/.claude/\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should not add since /.claude/ covers it
        expect($content)->not->toContain('/.claude/CLAUDE.md');
    });

    it('detects paths under ignored directory without leading slash', function () {
        file_put_contents("{$this->artifactPath}/.gitignore", ".claude/\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should not add since .claude/ covers it (second str_starts_with branch)
        expect($content)->not->toContain('/.claude/CLAUDE.md');
    });

    it('handles working directory with trailing slash', function () {
        // Create updater with trailing slash in working directory
        $updater = new GitignoreUpdater($this->artifactPath.'/');
        $updater->addPaths(['AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('/AGENTS.md');
    });

    it('handles absolute paths from working directory', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(["{$this->artifactPath}/AGENTS.md"]);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('/AGENTS.md');
        // Should not contain the full absolute path
        expect($content)->not->toContain($this->artifactPath);
    });

    it('preserves content when appending to existing gitignore', function () {
        $existingContent = "vendor/\nnode_modules/\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Original content should be preserved with proper spacing
        expect($content)->toStartWith("vendor/\nnode_modules/\n\n# MUSH GENERATED FILES");
    });

    it('trims existing content before appending mush section', function () {
        // Content with trailing whitespace
        $existingContent = "vendor/\n\n\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Should have cleaned up trailing newlines and added proper spacing
        expect($content)->toContain("vendor/\n\n# MUSH GENERATED FILES");
    });

    it('filters out comment lines when checking existing entries', function () {
        // Gitignore with a comment that looks like a path
        file_put_contents("{$this->artifactPath}/.gitignore", "# /AGENTS.md - this is a comment\n");

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['AGENTS.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        // Should add the path since the comment doesn't count as an entry
        expect($content)->toContain("# MUSH GENERATED FILES\n/AGENTS.md");
    });

    it('handles mush markers with whitespace', function () {
        $existingContent = "  # MUSH GENERATED FILES  \n/AGENTS.md\n  # END MUSH GENERATED FILES  \n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Should append to existing section despite whitespace
        expect($content)->toContain('/.cursor/mcp.json');
        expect(substr_count($content, 'MUSH GENERATED FILES'))->toBe(2); // START and END
    });

    it('never modifies content outside mush section markers', function () {
        $beforeSection = "# Dependencies\nvendor/\nnode_modules/\n\n# Build artifacts\n/dist/\n/build/";
        $afterSection = "\n\n# IDE files\n.idea/\n.vscode/\n\n# Environment\n.env\n.env.local";
        $mushSection = "# MUSH GENERATED FILES\n/AGENTS.md\n/.claude/CLAUDE.md\n# END MUSH GENERATED FILES";

        $existingContent = "{$beforeSection}\n\n{$mushSection}{$afterSection}";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json', 'GEMINI.md', '.junie/mcp.json']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Content BEFORE the mush section must be exactly preserved
        expect($content)->toStartWith($beforeSection);

        // Content AFTER the mush section must be exactly preserved
        expect($content)->toEndWith($afterSection);

        // New paths should be added within the mush section
        expect($content)->toContain('/.cursor/mcp.json');
        expect($content)->toContain('/GEMINI.md');
        expect($content)->toContain('/.junie/mcp.json');

        // Original mush section paths should still be there
        expect($content)->toContain('/AGENTS.md');
        expect($content)->toContain('/.claude/CLAUDE.md');
    });

    it('preserves exact content outside mush section on multiple updates', function () {
        $beforeSection = "# My custom rules\n*.log\n*.tmp\nsecrets.json";
        $afterSection = "\n\n# Coverage\ncoverage/\n.nyc_output/";
        $mushSection = "# MUSH GENERATED FILES\n/AGENTS.md\n# END MUSH GENERATED FILES";

        $existingContent = "{$beforeSection}\n\n{$mushSection}{$afterSection}";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);

        // First update
        $updater->addPaths(['.claude/CLAUDE.md']);
        $contentAfterFirst = file_get_contents("{$this->artifactPath}/.gitignore");

        // Second update
        $updater->addPaths(['.cursor/mcp.json']);
        $contentAfterSecond = file_get_contents("{$this->artifactPath}/.gitignore");

        // Third update
        $updater->addPaths(['GEMINI.md']);
        $contentAfterThird = file_get_contents("{$this->artifactPath}/.gitignore");

        // All updates should preserve content outside mush section
        foreach ([$contentAfterFirst, $contentAfterSecond, $contentAfterThird] as $content) {
            expect($content)->toStartWith($beforeSection);
            expect($content)->toEndWith($afterSection);
        }

        // Final content should have all paths
        expect($contentAfterThird)->toContain('/AGENTS.md');
        expect($contentAfterThird)->toContain('/.claude/CLAUDE.md');
        expect($contentAfterThird)->toContain('/.cursor/mcp.json');
        expect($contentAfterThird)->toContain('/GEMINI.md');
    });
});
