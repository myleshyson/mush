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

    it('adds comment header for fusion paths', function () {
        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.claude/CLAUDE.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");
        expect($content)->toContain('# Fusion generated files');
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

    it('appends to existing Fusion section instead of creating new one', function () {
        $existingContent = "vendor/\n\n# Fusion generated files\n/AGENTS.md\n/.claude/CLAUDE.md\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json', 'GEMINI.md']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Should only have one Fusion header
        expect(substr_count($content, '# Fusion generated files'))->toBe(1);

        // Should contain all paths
        expect($content)->toContain('/AGENTS.md');
        expect($content)->toContain('/.claude/CLAUDE.md');
        expect($content)->toContain('/.cursor/mcp.json');
        expect($content)->toContain('/GEMINI.md');
    });

    it('appends to Fusion section even when other sections follow', function () {
        $existingContent = "# Fusion generated files\n/AGENTS.md\n\n# Other stuff\n/build/\n";
        file_put_contents("{$this->artifactPath}/.gitignore", $existingContent);

        $updater = new GitignoreUpdater($this->artifactPath);
        $updater->addPaths(['.cursor/mcp.json']);

        $content = file_get_contents("{$this->artifactPath}/.gitignore");

        // Should only have one Fusion header
        expect(substr_count($content, '# Fusion generated files'))->toBe(1);

        // New path should be added to Fusion section
        expect($content)->toContain('/.cursor/mcp.json');

        // Other section should still be there
        expect($content)->toContain('# Other stuff');
        expect($content)->toContain('/build/');
    });
});
