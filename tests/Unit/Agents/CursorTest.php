<?php

use Myleshyson\Mush\Agents\Cursor\Cursor;

beforeEach(function () {
    $this->artifactPath = __DIR__.'/../../artifacts/agents/cursor';
    cleanDirectory($this->artifactPath);
    mkdir($this->artifactPath, 0777, true);
});

afterEach(function () {
    cleanDirectory($this->artifactPath);
});

describe('Cursor', function () {
    it('returns correct name', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->name())->toBe('Cursor');
    });

    it('returns correct paths', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->guidelines()->path())->toBe('.cursor/rules/mush.mdc');
        expect($agent->skills()->path())->toBe('.cursor/skills/');
        expect($agent->mcp()->path())->toBe('.cursor/mcp.json');
    });

    it('returns correct detection paths', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->detectionPaths())->toBe([
            '.cursorrules',  // Legacy format (still detected for migration)
            '.cursor/',
            '.cursor/rules/',
            '.cursor/mcp.json',
        ]);
    });

    it('writes guidelines with MDC frontmatter', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->guidelines()->write('# Test Guidelines');

        expect("{$this->artifactPath}/.cursor/rules/mush.mdc")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.cursor/rules/mush.mdc");
        expect($content)->toBe("---\nalwaysApply: true\n---\n\n# Test Guidelines");
    });

    it('transforms MCP config correctly', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([
            'server' => [
                'command' => ['npx', 'server'],
                'env' => ['KEY' => 'val'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['server']['command'])->toBe('npx');
        expect($config['mcpServers']['server']['args'])->toBe(['server']);
        expect($config['mcpServers']['server']['env'])->toBe(['KEY' => 'val']);
    });

    it('merges MCP config with existing', function () {
        mkdir("{$this->artifactPath}/.cursor", 0777, true);
        file_put_contents("{$this->artifactPath}/.cursor/mcp.json", json_encode([
            'mcpServers' => ['old' => ['command' => 'old']],
        ]));

        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['url' => 'https://example.com'],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['old'])->toBe(['command' => 'old']);
        expect($config['mcpServers']['new']['url'])->toBe('https://example.com');
    });

    it('continues processing after invalid config (not break)', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([
            'first' => ['command' => ['cmd1', 'arg1']],
            'invalid' => 'not-an-array',
            'third' => ['command' => ['cmd3', 'arg3']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        // Both valid configs should be present - proves continue not break
        expect($config['mcpServers'])->toHaveKey('first');
        expect($config['mcpServers'])->toHaveKey('third');
    });

    it('creates args only when command has more than 1 element', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([
            'single' => ['command' => ['only-command']],
            'double' => ['command' => ['cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        // Single element: no args key
        expect($config['mcpServers']['single']['command'])->toBe('only-command');
        expect($config['mcpServers']['single'])->not->toHaveKey('args');
        // Two elements: has args
        expect($config['mcpServers']['double']['command'])->toBe('cmd');
        expect($config['mcpServers']['double']['args'])->toBe(['arg']);
    });

    it('includes env when present in local server config', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([
            'with-env' => [
                'command' => ['cmd'],
                'env' => ['VAR' => 'value'],
            ],
            'without-env' => [
                'command' => ['cmd2'],
            ],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['with-env']['env'])->toBe(['VAR' => 'value']);
        expect($config['mcpServers']['without-env'])->not->toHaveKey('env');
    });

    it('merges when existing file has non-array mcpServers', function () {
        mkdir("{$this->artifactPath}/.cursor", 0777, true);
        file_put_contents("{$this->artifactPath}/.cursor/mcp.json", json_encode([
            'mcpServers' => 'invalid-string',
            'otherKey' => 'preserved',
        ]));

        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['otherKey'])->toBe('preserved');
    });

    it('correctly merges when both existing and new have servers', function () {
        mkdir("{$this->artifactPath}/.cursor", 0777, true);
        file_put_contents("{$this->artifactPath}/.cursor/mcp.json", json_encode([
            'mcpServers' => [
                'existing' => ['command' => 'old', 'env' => ['OLD' => 'val']],
            ],
        ]));

        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([
            'new' => ['command' => ['new-cmd', 'arg']],
        ]);

        $config = json_decode(file_get_contents("{$this->artifactPath}/.cursor/mcp.json"), true);
        // Both servers should exist
        expect($config['mcpServers'])->toHaveKey('existing');
        expect($config['mcpServers'])->toHaveKey('new');
        expect($config['mcpServers']['existing']['command'])->toBe('old');
        expect($config['mcpServers']['new']['command'])->toBe('new-cmd');
        expect($config['mcpServers']['new']['args'])->toBe(['arg']);
    });

    it('writes empty MCP config as object not array', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->mcp()->write([]);

        $rawContent = file_get_contents("{$this->artifactPath}/.cursor/mcp.json");
        // Ensure the JSON contains {} not [] for mcpServers
        expect($rawContent)->toContain('"mcpServers": {}');

        $config = json_decode($rawContent, true);
        expect($config['mcpServers'])->toBe([]);
    });

    it('returns null for agents (custom modes deprecated in Cursor 2.1)', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->agents())->toBeNull();
    });

    it('returns correct commands path', function () {
        $agent = new Cursor($this->artifactPath);
        expect($agent->commands()->path())->toBe('.cursor/commands/');
    });

    it('writes commands correctly', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->commands()->write([
            'test-command' => [
                'name' => 'test-command',
                'description' => 'A test command',
                'content' => '# Command Instructions',
            ],
        ]);

        expect("{$this->artifactPath}/.cursor/commands/test-command.md")->toBeFile();
        $content = file_get_contents("{$this->artifactPath}/.cursor/commands/test-command.md");
        // Cursor commands are plain markdown, no frontmatter
        expect($content)->toBe('# Command Instructions');
    });

    it('writes multiple commands', function () {
        $agent = new Cursor($this->artifactPath);
        $agent->commands()->write([
            'cmd1' => [
                'name' => 'cmd1',
                'description' => 'First command',
                'content' => 'Content 1',
            ],
            'cmd2' => [
                'name' => 'cmd2',
                'description' => 'Second command',
                'content' => 'Content 2',
            ],
        ]);

        expect("{$this->artifactPath}/.cursor/commands/cmd1.md")->toBeFile();
        expect("{$this->artifactPath}/.cursor/commands/cmd2.md")->toBeFile();
    });
});
